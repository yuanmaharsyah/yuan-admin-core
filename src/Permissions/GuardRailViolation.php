<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Permissions;

use RuntimeException;

/**
 * A model-level RBAC guard rail (spec §6.3) was about to be broken. Screens
 * turn it into a friendly error; nothing catches it silently.
 */
final class GuardRailViolation extends RuntimeException
{
    public static function lastSuperAdmin(): self
    {
        return new self('There must always be at least one active Super Admin.');
    }

    public static function protectedLevel(): self
    {
        return new self('The Super Admin level cannot be changed or deleted.');
    }

    public static function levelInUse(): self
    {
        return new self('A level that still has users cannot be deleted.');
    }

    public static function directPermissions(): self
    {
        return new self('A user gets permissions only through their level: use assignLevel().');
    }
}
