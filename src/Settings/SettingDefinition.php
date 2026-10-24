<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Settings;

use InvalidArgumentException;

/**
 * One key of `admin-core.settings` (spec §7.3): `{group}.{name}`, its type,
 * default, extra rules, label and whether pages get it (`public`).
 */
final readonly class SettingDefinition
{
    /**
     * @param  list<mixed>  $rules
     * @param  array<string, string>  $options
     */
    public function __construct(
        public string $key,
        public string $group,
        public string $name,
        public SettingType $type,
        public string $label,
        public mixed $default = null,
        public array $rules = [],
        public bool $public = false,
        public array $options = [],
        public ?string $help = null,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $group, string $name, array $config): self
    {
        $key = "{$group}.{$name}";
        $type = SettingType::tryFrom(is_string($config['type'] ?? null) ? $config['type'] : '')
            ?? throw new InvalidArgumentException("Setting [{$key}]: unknown type.");

        if ($type === SettingType::Image && ! array_key_exists($key, BrandingStorage::FIELDS)) {
            throw new InvalidArgumentException("Setting [{$key}]: only the branding fields may be images (AGENTS §5).");
        }

        if ($type === SettingType::Social && ! array_key_exists($name, SettingType::SOCIAL)) {
            throw new InvalidArgumentException("Setting [{$key}]: a social setting is named after its platform (".implode(', ', array_keys(SettingType::SOCIAL)).').');
        }

        /** @var list<mixed> $rules */
        $rules = is_array($config['rules'] ?? null) ? array_values($config['rules']) : [];
        /** @var array<string, string> $options */
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];

        return new self(
            key: $key,
            group: $group,
            name: $name,
            type: $type,
            label: is_string($config['label'] ?? null) ? $config['label'] : $name,
            default: $config['default'] ?? null,
            rules: $rules,
            public: (bool) ($config['public'] ?? false),
            options: $options,
            help: is_string($config['help'] ?? null) ? $config['help'] : null,
        );
    }

    /** The platform of a social setting: its name. */
    public function platform(): string
    {
        return $this->name;
    }
}
