<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

require_once __DIR__ . '/../Common/DependencyInjection.php';
require_once __DIR__ . '/../Infrastructure/Adapters/Persistence/MySQL/Config/MigrationRunner.php';

DependencyInjection::boot();

try {
    $runner = new MigrationRunner(DependencyInjection::getPdo());
    $applied = $runner->migrate(__DIR__ . '/../Infrastructure/Adapters/Persistence/MySQL/Migrations');
    fwrite(STDOUT, $applied === array()
        ? "La base ya está actualizada.\n"
        : 'Migraciones aplicadas: ' . implode(', ', $applied) . "\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migración detenida: ' . get_class($exception) . ". Verifica precondiciones y copia de seguridad.\n");
    exit(1);
}
