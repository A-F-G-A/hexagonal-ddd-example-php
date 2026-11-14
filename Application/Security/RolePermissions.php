<?php

declare(strict_types=1);

/** Server-side authorization policy for web and API use cases. */
final class RolePermissions
{
    private const PERMISSIONS = array(
        'ADMIN' => array('*'),
        'SUPERVISOR' => array(
            'users.read',
            'celulares.read', 'celulares.create', 'celulares.update', 'celulares.delete',
            'dashboard.read', 'activity.read'
        ),
        'OPERADOR' => array(
            'celulares.read', 'celulares.create', 'celulares.update', 'dashboard.read'
        ),
        'CONSULTA' => array('celulares.read', 'dashboard.read'),
    );

    private const LEGACY_ROLES = array(
        'MEMBER' => 'OPERADOR',
        'REVIEWER' => 'CONSULTA',
    );

    public static function normalizeRole(string $role): string
    {
        $normalized = strtoupper(trim($role));

        return self::LEGACY_ROLES[$normalized] ?? $normalized;
    }

    public static function allows(string $role, string $permission): bool
    {
        $permissions = self::PERMISSIONS[self::normalizeRole($role)] ?? array();

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    /** @return string[] */
    public static function roles(): array
    {
        return array_keys(self::PERMISSIONS);
    }
}
