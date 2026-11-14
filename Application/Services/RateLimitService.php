<?php

declare(strict_types=1);

require_once __DIR__ . '/../Ports/Out/RateLimitPort.php';

final class RateLimitService
{
    public function __construct(private RateLimitPort $port, private string $secret)
    {
        if (strlen($secret) < 32 || str_starts_with($secret, 'replace_')) {
            throw new InvalidArgumentException('APP_SECRET debe tener al menos 32 caracteres.');
        }
    }

    public function allow(string $scope): bool
    {
        return $this->port->consume(hash_hmac('sha256', $scope, $this->secret), 5, 900);
    }

    public function clear(string $scope): void
    {
        $this->port->clear(hash_hmac('sha256', $scope, $this->secret));
    }
}
