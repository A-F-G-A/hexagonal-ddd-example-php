<?php

declare(strict_types=1);

if (getenv('RUN_DB_INTEGRATION') !== '1') {
    fwrite(STDERR, "Define RUN_DB_INTEGRATION=1 solo para una base de datos descartable.\n");
    exit(2);
}

require_once __DIR__ . '/../Common/DependencyInjection.php';
require_once __DIR__ . '/../Common/Uuid.php';
require_once __DIR__ . '/../Domain/Models/Celular.php';
require_once __DIR__ . '/../Domain/Models/UserModel.php';
require_once __DIR__ . '/../Domain/ValueObjects/UserId.php';
require_once __DIR__ . '/../Domain/ValueObjects/UserName.php';
require_once __DIR__ . '/../Domain/ValueObjects/UserEmail.php';
require_once __DIR__ . '/../Domain/ValueObjects/UserPassword.php';
require_once __DIR__ . '/../Domain/Enums/UserRoleEnum.php';
require_once __DIR__ . '/../Domain/Enums/UserStatusEnum.php';
require_once __DIR__ . '/../Infrastructure/Adapters/Persistence/MySQL/Mapper/CelularPersistenceMapper.php';
require_once __DIR__ . '/../Infrastructure/Adapters/Persistence/MySQL/Repository/CelularRepositoryMySQL.php';
require_once __DIR__ . '/../Infrastructure/Adapters/Persistence/MySQL/Repository/PasswordResetRepositoryMySQL.php';
require_once __DIR__ . '/../Infrastructure/Adapters/Persistence/MySQL/Repository/RateLimitRepositoryMySQL.php';
require_once __DIR__ . '/../Infrastructure/Adapters/Persistence/MySQL/Repository/ApiTokenRepositoryMySQL.php';
require_once __DIR__ . '/../Infrastructure/Adapters/Persistence/MySQL/Repository/ActivityLogRepositoryMySQL.php';

DependencyInjection::boot();
$pdo = DependencyInjection::getPdo();
$users = DependencyInjection::getUserRepository();
$celulares = new CelularRepositoryMySQL($pdo, new CelularPersistenceMapper());
$reset = new PasswordResetRepositoryMySQL($pdo);
$limits = new RateLimitRepositoryMySQL($pdo);
$tokens = new ApiTokenRepositoryMySQL($pdo);
$activity = new ActivityLogRepositoryMySQL($pdo);
$userId = Uuid::v4();
$email = 'integration+' . bin2hex(random_bytes(8)) . '@example.test';
$celularId = Uuid::v4();
$imei = '490154203237518';
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException('Integration assertion failed: ' . $message);
    }
};

$userSaved = false;
$celularSaved = false;
$limitKey = hash('sha256', random_bytes(32));
try {
    $user = new UserModel(
        new UserId($userId),
        new UserName('Integration Admin'),
        new UserEmail($email),
        UserPassword::fromPlainText('integration-password-123'),
        UserRoleEnum::ADMIN,
        UserStatusEnum::ACTIVE
    );
    $users->save($user);
    $userSaved = true;
    $assert($users->getByEmail(new UserEmail($email)) !== null, 'user insert and lookup');
    $assert($users->countActive() > 0, 'active user count');
    $assert(count($users->getAll(1, 0)) <= 1 && $users->countAll() > 0, 'bounded user pagination');

    $celular = new Celular(
        $celularId, 'Integration Device', array($imei), '6.70', '50.00', 8, 256, 0,
        'Android', 'Libre', '5G', true, true, 3, 'Qualcomm', '3.20', true, true, false, true, 1
    );
    $celulares->save($celular);
    $celularSaved = true;
    $loaded = $celulares->getById($celularId);
    $assert($loaded !== null && $loaded->imei() === array($imei), 'cellphone insert and IMEI hydration');
    $prefix = '35693803564380';
    $sum = 0;
    for ($index = 0; $index < 14; $index++) {
        $digit = (int) $prefix[$index];
        if ($index % 2 === 1) { $digit *= 2; if ($digit > 9) $digit -= 9; }
        $sum += $digit;
    }
    $secondImei = $prefix . ((10 - ($sum % 10)) % 10);
    $dualSim = new Celular(
        $celularId, 'Integration Device', array($imei, $secondImei), '6.70', '50.00', 8, 256, 0,
        'Android', 'Libre', '5G', true, true, 3, 'Qualcomm', '3.20', true, true, false, true, 2
    );
    $celulares->update($dualSim);
    $assert(count($celulares->getById($celularId)->imei()) === 2, 'transactional dual-SIM update');
    $duplicateRejected = false;
    try {
        $celulares->save(new Celular(
            Uuid::v4(), 'Duplicate Device', array($imei), '6.10', '12.00', 4, 64, 0,
            'Android', 'Libre', '4G', true, true, 2, 'Qualcomm', '2.00', false, false, false, false, 1
        ));
    } catch (PDOException $exception) {
        $duplicateRejected = (string) $exception->getCode() === '23000';
    }
    $assert($duplicateRejected && $celulares->getById($celularId) !== null, 'unique IMEI rejects duplicates without damaging the original');
    $result = $celulares->search(new CelularQuery(search: 'Integration Device', page: 1, perPage: 10));
    $assert($result['total'] === 1 && $result['items'][0]->id() === $celularId, 'cellphone search and pagination');

    $resetToken = bin2hex(random_bytes(32));
    $reset->issue($userId, hash('sha256', $resetToken), gmdate('Y-m-d H:i:s', time() + 600));
    $newPasswordHash = UserPassword::fromPlainText('new-integration-password-456')->value();
    $assert($reset->consumeAndReplacePassword(hash('sha256', $resetToken), $newPasswordHash), 'reset token atomically updates password');
    $assert(!$reset->consumeAndReplacePassword(hash('sha256', $resetToken), $newPasswordHash), 'reset token is one time');
    $assert($users->getByEmail(new UserEmail($email))->password()->verifyPlain('new-integration-password-456'), 'reset password hash verifies');

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $assert($limits->consume($limitKey, 5, 900), 'rate limit permits attempt ' . $attempt);
    }
    $assert(!$limits->consume($limitKey, 5, 900), 'rate limit blocks attempt above threshold');
    $limits->clear($limitKey);

    $rawToken = 'cat_' . bin2hex(random_bytes(32));
    $tokenId = Uuid::v4();
    $tokens->issue($tokenId, $userId, 'integration', hash('sha256', $rawToken), gmdate('Y-m-d H:i:s', time() + 600));
    $assert(($tokens->authenticate(hash('sha256', $rawToken))['id'] ?? null) === $userId, 'API token authenticates active account');
    $assert($tokens->revoke($tokenId), 'API token revocation');
    $assert($tokens->authenticate(hash('sha256', $rawToken)) === null, 'revoked API token is rejected');

    $activity->record($userId, 'integration.checked', 'user', $userId);
    $recent = $activity->recent(1);
    $assert(($recent[0]['action'] ?? null) === 'integration.checked', 'activity log write and query');

    $celulares->delete($celularId);
    $celularSaved = false;
    $assert($celulares->getById($celularId) === null, 'cellphone delete');
    fwrite(STDOUT, 'OK ' . $assertions . " MySQL integration assertions\n");
} finally {
    if ($celularSaved) {
        $celulares->delete($celularId);
    }
    if ($userSaved) {
        $users->delete(new UserId($userId));
    }
    $limits->clear($limitKey);
}
