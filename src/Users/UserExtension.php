<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Users;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Yuan\AdminCore\Data\OptionData;
use Yuan\AdminCore\Data\Users\ExtraFieldData;
use Yuan\AdminCore\Data\Users\ExtraValueData;

/** Reads the configured UserFormExtension; with none, every method returns nothing. */
final class UserExtension
{
    public static function current(): ?UserFormExtension
    {
        $class = config('admin-core.users.form_extension');

        if ($class === null || $class === '') {
            return null;
        }

        if (! is_string($class) || ! is_subclass_of($class, UserFormExtension::class)) {
            throw new InvalidArgumentException('admin-core.users.form_extension must name a class implementing '.UserFormExtension::class.'.');
        }

        return app($class);
    }

    /**
     * @return list<ExtraFieldData>
     */
    public static function fields(): array
    {
        return self::current()?->fields() ?? [];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(self::fields(), 'name');
    }

    /**
     * The rules for the `extra.*` input of the Users forms.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(?Model $user): array
    {
        $rules = [];

        foreach (self::current()?->rules($user) ?? [] as $name => $fieldRules) {
            $rules["extra.{$name}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return list<ExtraValueData>
     */
    public static function values(Model $user): array
    {
        return array_map(function (ExtraFieldData $field) use ($user): ExtraValueData {
            $value = $user->getAttribute($field->name);
            $value = is_scalar($value) && ! is_bool($value) ? $value : null;
            $option = array_find($field->options, fn (OptionData $option): bool => $option->value === (string) $value);

            return new ExtraValueData(
                name: $field->name,
                label: $field->label,
                value: $value,
                display: $value === null ? null : ($option->label ?? (string) $value),
            );
        }, self::fields());
    }
}
