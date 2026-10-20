<?php

declare(strict_types=1);

namespace Yuan\AdminCore\DataTable;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Records `visibleTo()` calls while a test asks for it, so a module test can
 * prove a route scopes its query to the signed-in user. Outside `record()` it
 * keeps nothing.
 */
final class VisibilityScope
{
    /** @var list<array{model: class-string, user: mixed}>|null */
    private static ?array $calls = null;

    /**
     * @param  class-string  $model
     */
    public static function applied(string $model, ?Authenticatable $user): void
    {
        if (self::$calls !== null) {
            self::$calls[] = ['model' => $model, 'user' => $user?->getAuthIdentifier()];
        }
    }

    /**
     * @return list<array{model: class-string, user: mixed}>
     */
    public static function record(callable $callback): array
    {
        self::$calls = [];

        try {
            $callback();

            return self::$calls;
        } finally {
            self::$calls = null;
        }
    }
}
