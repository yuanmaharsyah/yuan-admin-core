<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Settings\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Settings\SettingDefinition;
use Yuan\AdminCore\Settings\Settings;

/** A Settings tab's save: field name => value (an image as an upload); rules from the registry. */
#[TypeScript]
final class UpdateSettingsData extends Data
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public int $lock_version,
        public array $values = [],
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        $rules = ['lock_version' => ['required', 'integer', 'min:0'], 'values' => ['array']];

        foreach (self::fields() as $definition) {
            $rules["values.{$definition->name}"] = $definition->type->rules($definition);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return array_combine(
            array_map(fn (SettingDefinition $definition): string => "values.{$definition->name}", self::fields()),
            array_map(fn (SettingDefinition $definition): string => mb_strtolower(__($definition->label)), self::fields()),
        );
    }

    /**
     * @return list<SettingDefinition>
     */
    private static function fields(): array
    {
        $group = request()->route('group');

        return is_string($group) ? array_values(app(Settings::class)->group($group)) : [];
    }
}
