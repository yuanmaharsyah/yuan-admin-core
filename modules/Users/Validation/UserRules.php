<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Users\Validation;

use Closure;
use Illuminate\Validation\Rule;
use Yuan\AdminCore\Modules\Users\Models\User;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Users\UserExtension;

/**
 * The rules both user forms share. A username never contains `@` (the login
 * field reads `@` as an email); the Super Admin level is only for Super Admin
 * to give (spec §6.3).
 */
final class UserRules
{
    /**
     * @return array<string, list<mixed>>
     */
    public static function for(?User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'level_id' => ['required', 'integer', self::assignableLevel()],
            'is_active' => ['boolean'],
            'extra' => ['array'],
            ...UserExtension::rules($user),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return ['username.regex' => __('Username hanya boleh berisi huruf, angka, titik, garis bawah dan tanda hubung (tanpa "@").')];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        $extra = [];

        foreach (UserExtension::fields() as $field) {
            $extra["extra.{$field->name}"] = $field->label;
        }

        return ['name' => __('nama'), 'username' => __('username'), 'email' => __('email'), 'level_id' => __('level'), 'is_active' => __('status'), ...$extra];
    }

    private static function assignableLevel(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $level = is_numeric($value) ? Level::query()->find((int) $value) : null;

            if ($level === null || ($level->is_protected && ! User::isSuperAdminViewer(auth()->user()))) {
                $fail('validation.exists')->translate();
            }
        };
    }
}
