<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../../Application/Ports/Out/RateLimitPort.php';

final class RateLimitRepositoryMySQL implements RateLimitPort
{
    public function __construct(private PDO $pdo)
    {
    }

    public function consume(string $keyHash, int $maximum, int $windowSeconds): bool
    {
        $this->pdo->beginTransaction();
        try {
            $insert = $this->pdo->prepare(
                'INSERT IGNORE INTO auth_rate_limits (scope_key, attempts, window_started_at) VALUES (:key, 0, UTC_TIMESTAMP())'
            );
            $insert->execute(array(':key' => $keyHash));

            $select = $this->pdo->prepare(
                'SELECT attempts, window_started_at, blocked_until FROM auth_rate_limits WHERE scope_key = :key FOR UPDATE'
            );
            $select->execute(array(':key' => $keyHash));
            $state = $select->fetch();
            if ($state === false) {
                throw new RuntimeException('No se pudo leer el límite de autenticación.');
            }

            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $windowStarted = new DateTimeImmutable((string) $state['window_started_at'], new DateTimeZone('UTC'));
            $blockedUntil = $state['blocked_until'] === null
                ? null
                : new DateTimeImmutable((string) $state['blocked_until'], new DateTimeZone('UTC'));

            if ($blockedUntil !== null && $blockedUntil > $now) {
                $this->pdo->commit();
                return false;
            }

            $attempts = (int) $state['attempts'];
            if (($now->getTimestamp() - $windowStarted->getTimestamp()) >= $windowSeconds) {
                $attempts = 0;
                $windowStarted = $now;
            }

            if ($attempts >= $maximum) {
                $blocked = $now->modify('+' . $windowSeconds . ' seconds');
                $update = $this->pdo->prepare(
                    'UPDATE auth_rate_limits SET attempts = :attempts, window_started_at = :started, blocked_until = :blocked WHERE scope_key = :key'
                );
                $update->execute(array(
                    ':attempts' => $attempts,
                    ':started' => $windowStarted->format('Y-m-d H:i:s'),
                    ':blocked' => $blocked->format('Y-m-d H:i:s'),
                    ':key' => $keyHash,
                ));
                $this->pdo->commit();
                return false;
            }

            $update = $this->pdo->prepare(
                'UPDATE auth_rate_limits SET attempts = :attempts, window_started_at = :started, blocked_until = NULL WHERE scope_key = :key'
            );
            $update->execute(array(
                ':attempts' => $attempts + 1,
                ':started' => $windowStarted->format('Y-m-d H:i:s'),
                ':key' => $keyHash,
            ));
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function clear(string $keyHash): void
    {
        $statement = $this->pdo->prepare('DELETE FROM auth_rate_limits WHERE scope_key = :key');
        $statement->execute(array(':key' => $keyHash));
    }
}
