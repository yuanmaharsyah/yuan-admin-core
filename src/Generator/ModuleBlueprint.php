<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Generator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Yuan\AdminCore\Audit\Masker;
use Yuan\AdminCore\Registry\InvalidModuleDefinition;
use Yuan\AdminCore\Registry\ModuleDefinition;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * Everything make:module knows about the module before writing a file: the
 * names, the fields, the actions and the targets of foreign fields, checked
 * the way ModuleDefinition checks a module.php (so bad input writes nothing).
 */
final readonly class ModuleBlueprint
{
    /** Names that look like personal data (UU PDP) without being in `audit.masked_fields`: a reminder is printed. */
    public const array PERSONAL_HINTS = ['nip', 'nik', 'npwp', 'ktp', 'kk', 'telepon', 'phone', 'hp', 'whatsapp', 'email', 'alamat', 'address', 'rekening', 'tanggal_lahir', 'birth', 'ibu_kandung'];

    /**
     * @param  list<string>  $actions
     * @param  list<FieldDefinition>  $fields
     * @param  array<string, ForeignTarget>  $targets  Field name => target.
     */
    public function __construct(
        public string $name,
        public string $slug,
        public string $label,
        public string $group,
        public string $icon,
        public array $actions,
        public bool $superadminOnly,
        public array $fields,
        public array $targets,
    ) {}

    /**
     * @param  list<string>  $actions
     */
    public static function make(string $name, string $fields, array $actions, string $group, string $icon, ?string $slug = null, ?string $label = null, bool $superadminOnly = false, bool $checkRegistry = true): self
    {
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) !== 1) {
            throw new InvalidArgumentException("Module name [{$name}]: use PascalCase in the singular, e.g. Invoice.");
        }

        $slug ??= Str::kebab(Str::pluralStudly($name));
        $actions = array_values(array_unique(['view', ...array_map(trim(...), $actions)]));
        $custom = array_diff($actions, ModuleDefinition::STANDARD_ACTIONS);

        if ($custom !== []) {
            throw new InvalidArgumentException('Custom actions ('.implode(', ', $custom).') need real behaviour: generate the standard ones, then follow DEVELOPER.md R3.');
        }

        $definitions = array_map(FieldDefinition::parse(...), array_values(array_filter(array_map(trim(...), preg_split('/,(?![^(]*\))/', $fields) ?: []), fn (string $spec): bool => $spec !== '')));
        $names = array_map(fn (FieldDefinition $field): string => $field->name, $definitions);

        if ($definitions === []) {
            throw new InvalidArgumentException('Give the module at least one field with --fields.');
        }

        if (count(array_unique($names)) !== count($names)) {
            throw new InvalidArgumentException('Field names must be unique.');
        }

        $blueprint = new self($name, $slug, $label ?? $name, $group, $icon, array_values(array_intersect(ModuleDefinition::STANDARD_ACTIONS, $actions)), $superadminOnly, $definitions, self::targets($definitions));
        $blueprint->validateDefinition($checkRegistry);

        return $blueprint;
    }

    /** The folder of the project module root (`admin-core.modules.roots` → `project`). */
    public static function projectRoot(): string
    {
        foreach (config()->array('admin-core.modules.roots') as $root) {
            if (is_array($root) && ($root['name'] ?? null) === 'project' && is_string($root['path'] ?? null)) {
                return $root['path'];
            }
        }

        return base_path('modules');
    }

    public function declares(string $action): bool
    {
        return in_array($action, $this->actions, true);
    }

    public function table(): string
    {
        return Str::snake(Str::pluralStudly($this->name));
    }

    /** The route parameter and PHP variable: `Invoice` → `invoice`. */
    public function variable(): string
    {
        return Str::camel($this->name);
    }

    /** The first string field: the record's label (auditLabel, title, delete dialog); null when there is none. */
    public function labelField(): ?FieldDefinition
    {
        foreach ($this->fields as $field) {
            if ($field->type === FieldType::String) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return list<FieldDefinition>
     */
    public function fieldsOf(FieldType ...$types): array
    {
        return array_values(array_filter($this->fields, fn (FieldDefinition $field): bool => in_array($field->type, $types, true)));
    }

    /**
     * @return list<FieldDefinition>
     */
    public function columns(): array
    {
        return array_values(array_filter($this->fields, fn (FieldDefinition $field): bool => $field->type->hasColumn()));
    }

    public function hasFiles(): bool
    {
        return $this->fieldsOf(FieldType::File) !== [];
    }

    /** The form has selects that need server-side choices. */
    public function hasOptions(): bool
    {
        return $this->fieldsOf(FieldType::Enum) !== [];
    }

    /**
     * Fields whose name `audit.masked_fields` masks (listed in the generated audit assertions).
     *
     * @return list<string>
     */
    public function maskedFields(): array
    {
        $masker = app(Masker::class);

        return array_values(array_map(fn (FieldDefinition $field): string => $field->name, array_filter($this->columns(), fn (FieldDefinition $field): bool => $masker->rule($field->name) !== null)));
    }

    /**
     * Fields that look like personal data but aren't masked (a reminder for R15).
     *
     * @return list<string>
     */
    public function unmaskedPersonalFields(): array
    {
        $masked = $this->maskedFields();

        return array_values(array_map(fn (FieldDefinition $field): string => $field->name, array_filter(
            $this->columns(),
            fn (FieldDefinition $field): bool => ! in_array($field->name, $masked, true)
                && array_any(self::PERSONAL_HINTS, fn (string $hint): bool => $field->name === $hint || str_starts_with($field->name, "{$hint}_") || str_ends_with($field->name, "_{$hint}")),
        )));
    }

    /** The module.php array, checked by ModuleDefinition (group, icon, actions, slug format). */
    private function validateDefinition(bool $checkRegistry): void
    {
        $registry = app(ModuleRegistry::class);
        $groups = array_keys(config()->array('admin-core.menu.groups'));

        try {
            ModuleDefinition::fromConfig($this->moduleConfig(), "{$this->name}/module.php", 'project', 'Modules\\', $groups);
        } catch (InvalidModuleDefinition $exception) {
            throw new InvalidArgumentException($exception->getMessage(), previous: $exception);
        }

        foreach ($checkRegistry ? $registry->all() : [] as $module) {
            if ($module->slug === $this->slug || $module->name === $this->name) {
                throw new InvalidArgumentException("A module named {$module->name} with slug {$module->slug} already exists ({$module->root} root).");
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function moduleConfig(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'label' => $this->label,
            'icon' => $this->icon,
            'group' => $this->group,
            'order' => 100,
            'enabled' => true,
            'core' => false,
            'superadmin_only' => $this->superadminOnly,
            'menu' => true,
            'actions' => $this->actions,
            'widgets' => [],
        ];
    }

    /**
     * The module behind each foreign field: its model uses the table, and it has a `lookup` route.
     *
     * @param  list<FieldDefinition>  $fields
     * @return array<string, ForeignTarget>
     */
    private static function targets(array $fields): array
    {
        $targets = [];

        foreach ($fields as $field) {
            if ($field->type !== FieldType::Foreign) {
                continue;
            }

            $found = null;

            foreach (app(ModuleRegistry::class)->enabled() as $module) {
                $model = "{$module->namespace}{$module->name}\\Models\\{$module->name}";

                if (class_exists($model) && is_subclass_of($model, Model::class) && (new $model)->getTable() === $field->table) {
                    $found = new ForeignTarget($model, $module->slug);
                }
            }

            if ($found === null) {
                throw new InvalidArgumentException("Field [{$field->name}]: no module's model uses the table [{$field->table}].");
            }

            if (! Route::has("{$found->slug}.lookup")) {
                throw new InvalidArgumentException("Field [{$field->name}]: module {$found->slug} has no lookup route for the RelationSelect. Add `GET lookup` (name `lookup`, permission:{$found->slug}.view, answering LookupData) to that module first (DEVELOPER.md R19 step 4).");
            }

            $targets[$field->name] = $found;
        }

        return $targets;
    }
}
