<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Generator;

use Illuminate\Support\Str;

/**
 * Builds every file of a module from a blueprint: the stubs in
 * `stubs/module`, filled with the per-field and per-action fragments built
 * here. The output follows the demo Invoice module (the approved patterns);
 * Pint and Prettier format it after it is written (MakeModuleCommand).
 */
final readonly class ModuleWriter
{
    public function __construct(
        private ModuleBlueprint $module,
        private string $migrationDate,
    ) {}

    /**
     * Path (relative to the module folder) => contents.
     *
     * @return array<string, string>
     */
    public function files(): array
    {
        $m = $this->module;
        $files = [
            'module.php' => $this->render('module/module', ['superadmin_only' => $m->superadminOnly ? 'true' : 'false', 'actions' => $this->list($m->actions)]),
            "Models/{$m->name}.php" => $this->model(),
            "Policies/{$m->name}Policy.php" => $this->policy(),
            "Data/{$m->name}Data.php" => $this->outputData("{$m->name}Data", $this->columnsWithLock(), "One {$this->lower()} record, for its Show and Edit pages."),
            "Data/{$m->name}ListItemData.php" => $this->outputData("{$m->name}ListItemData", array_values(array_filter($m->columns(), fn (FieldDefinition $field): bool => $field->type->listed())), "One row of the {$this->lower()} list."),
            "Http/Controllers/{$m->name}Controller.php" => $this->controller(),
            'Routes/web.php' => $this->routes(),
            "Database/Migrations/{$this->migrationDate}_create_{$m->table()}_table.php" => $this->render('module/migration', ['columns' => $this->lines($this->migrationColumns(), 12)]),
            "Database/Factories/{$m->name}Factory.php" => $this->factory(),
            "Database/Seeders/{$m->name}Seeder.php" => $this->render('module/seeder'),
            'resources/js/pages/Index.tsx' => $this->indexPage(),
            'resources/js/pages/Show.tsx' => $this->showPage(),
            'resources/js/lang/id.ts' => $this->render('module/js/lang-id', ['strings' => $this->strings()]),
            'resources/js/lang/en.ts' => $this->render('module/js/lang-en', ['strings' => $this->strings()]),
            "Tests/Feature/{$m->name}Test.php" => $this->tests(),
        ];

        foreach ($m->fieldsOf(FieldType::Enum) as $field) {
            $files["Enums/{$field->enumClass($m->name)}.php"] = $this->enum($field);
        }

        if ($m->declares('create')) {
            $files["Data/Store{$m->name}Data.php"] = $this->inputData(false);
            $files['resources/js/pages/Create.tsx'] = $this->formPage('Create');
        }

        if ($m->declares('edit')) {
            $files["Data/Update{$m->name}Data.php"] = $this->inputData(true);
            $files['resources/js/pages/Edit.tsx'] = $this->formPage('Edit');
        }

        if ($this->hasForm()) {
            $files["resources/js/components/{$m->name}Form.tsx"] = $this->form();

            if ($m->hasOptions()) {
                $files["Data/{$m->name}FormOptionsData.php"] = $this->formOptions();
            }
        }

        if ($m->declares('download')) {
            $files["Exports/{$m->name}Export.php"] = $this->export();
        }

        ksort($files);

        return $files;
    }

    // ---- shared names ------------------------------------------------------

    /**
     * @param  array<string, string>  $values
     */
    private function render(string $stub, array $values = []): string
    {
        $m = $this->module;

        return StubRenderer::render($stub, [
            'Name' => $m->name,
            'variable' => $m->variable(),
            'slug' => $m->slug,
            'plural' => $this->plural(),
            'label' => $m->label,
            'lower_label' => $this->lower(),
            'icon' => $m->icon,
            'group' => $m->group,
            'table' => $m->table(),
            ...$values,
        ]);
    }

    private function lower(): string
    {
        return Str::lower($this->module->label);
    }

    /** The list prop: `Invoice` → `invoices`. */
    private function plural(): string
    {
        return Str::camel(Str::pluralStudly($this->module->name));
    }

    private function base(): string
    {
        return '/'.trim(config()->string('admin-core.routes.prefix'), '/').'/'.$this->module->slug;
    }

    private function hasForm(): bool
    {
        return $this->module->declares('create') || $this->module->declares('edit');
    }

    /**
     * @param  list<string>  $items
     */
    private function list(array $items): string
    {
        return implode(', ', array_map(fn (string $item): string => "'{$item}'", $items));
    }

    /**
     * @param  list<string>  $lines
     */
    private function lines(array $lines, int $indent): string
    {
        return implode("\n", array_map(fn (string $line): string => $line === '' ? '' : str_repeat(' ', $indent).$line, $lines));
    }

    /**
     * @param  array<string, true>  $imports
     */
    private function phpImports(array $imports): string
    {
        $names = array_keys($imports);
        sort($names);

        return implode("\n", array_map(fn (string $name): string => "use {$name};", $names));
    }

    /**
     * @param  array<string, array<string, true>>  $imports  Module => named imports ('default:X' and 'type:X' too).
     */
    private function tsImports(array $imports): string
    {
        $lines = [];

        foreach ($imports as $from => $names) {
            $names = array_keys($names);
            $types = array_map(fn (string $name): string => substr($name, 5), array_values(array_filter($names, fn (string $name): bool => str_starts_with($name, 'type:'))));
            $values = array_values(array_filter($names, fn (string $name): bool => ! str_starts_with($name, 'type:')));
            sort($values);
            sort($types);

            if ($values !== []) {
                $lines[] = 'import { '.implode(', ', $values)." } from '{$from}';";
            }

            if ($types !== []) {
                $lines[] = 'import type { '.implode(', ', $types)." } from '{$from}';";
            }
        }

        return implode("\n", $lines);
    }

    /** `$this->can('edit')` in TSX: the permission check, or Super Admin for a superadmin_only module. */
    private function can(string $action): string
    {
        return $this->module->superadminOnly ? 'isSuperAdmin' : "can('{$this->module->slug}.{$action}')";
    }

    /**
     * Column fields, plus `lock_version` when the module declares `edit`.
     *
     * @return list<FieldDefinition|null> null stands for lock_version.
     */
    private function columnsWithLock(): array
    {
        return [...$this->module->columns(), ...($this->module->declares('edit') ? [null] : [])];
    }

    private function enumClass(FieldDefinition $field): string
    {
        return $field->enumClass($this->module->name);
    }

    private function target(FieldDefinition $field): ForeignTarget
    {
        return $this->module->targets[$field->name];
    }

    // ---- PHP ---------------------------------------------------------------

    private function model(): string
    {
        $m = $this->module;
        $imports = [
            'Illuminate\Database\Eloquent\Attributes\UseFactory' => true,
            'Illuminate\Database\Eloquent\Factories\HasFactory' => true,
            'Illuminate\Database\Eloquent\Model' => true,
            'Illuminate\Database\Eloquent\SoftDeletes' => true,
            'Illuminate\Support\Carbon' => true,
            "Modules\\{$m->name}\\Database\\Factories\\{$m->name}Factory" => true,
            'Yuan\AdminCore\Audit\HasActivityHistory' => true,
            'Yuan\AdminCore\Audit\LogsModuleActivity' => true,
            'Yuan\AdminCore\DataTable\DataTableModel' => true,
            'Yuan\AdminCore\DataTable\HasDataTable' => true,
            'Yuan\AdminCore\DataTable\HasVisibilityScope' => true,
        ];
        $traits = ['HasActivityHistory', 'HasDataTable', 'HasFactory', 'HasVisibilityScope', 'LogsModuleActivity', 'SoftDeletes'];
        $properties = [' * @property int $id'];
        $casts = [];
        $body = [];

        if ($m->declares('edit')) {
            $imports['Yuan\AdminCore\Concurrency\PreventsStaleUpdates'] = true;
            $traits[] = 'PreventsStaleUpdates';
            $body[] = '';
            $body[] = '    /** @var array<string, mixed> */';
            $body[] = "    protected \$attributes = ['lock_version' => 0];";
        }

        if ($m->hasFiles()) {
            $imports['Yuan\AdminCore\Attachments\HasAttachments'] = true;
            $imports['Yuan\AdminCore\Attachments\AttachmentSlot'] = true;
            $traits[] = 'HasAttachments';
        }

        sort($traits);
        $body[] = '';
        $body[] = '    protected $fillable = ['.$this->list(array_map(fn (FieldDefinition $field): string => $field->name, $m->columns())).'];';

        foreach ($m->columns() as $field) {
            $null = $field->nullable ? '|null' : '';
            [$type, $cast] = match ($field->type) {
                FieldType::String, FieldType::Text => ['string', null],
                FieldType::Integer, FieldType::Year, FieldType::Currency => ['int', "'integer'"],
                FieldType::Decimal => ['string', "'decimal:3'"],
                FieldType::Boolean => ['bool', "'boolean'"],
                FieldType::Date => ['CarbonImmutable', "'immutable_date'"],
                FieldType::DateTime => ['CarbonImmutable', "'immutable_datetime'"],
                FieldType::Enum => [$this->enumClass($field), $this->enumClass($field).'::class'],
                FieldType::Foreign => ['int', "'integer'"],
                FieldType::File => ['', null],
            };
            $properties[] = " * @property {$type}{$null} \${$field->name}";

            if ($cast !== null) {
                $casts[] = "'{$field->name}' => {$cast},";
            }

            if (in_array($field->type, [FieldType::Date, FieldType::DateTime], true)) {
                $imports['Carbon\CarbonImmutable'] = true;
            }

            if ($field->type === FieldType::Enum) {
                $imports["Modules\\{$m->name}\\Enums\\{$this->enumClass($field)}"] = true;
            }

            if ($field->type === FieldType::Foreign) {
                $target = $this->target($field);
                $imports[$target->model] = true;
                $imports['Illuminate\Database\Eloquent\Relations\BelongsTo'] = true;
                $properties[] = " * @property-read {$target->shortName()}".($field->nullable ? '|null' : '')." \${$field->relation()}";
                array_push($body, '', '    /**', "     * @return BelongsTo<{$target->shortName()}, \$this>", '     */', "    public function {$field->relation()}(): BelongsTo", '    {', "        return \$this->belongsTo({$target->shortName()}::class, '{$field->name}')->withTrashed();", '    }');
            }
        }

        $properties[] = ' * @property Carbon $created_at';
        $properties[] = ' * @property Carbon $updated_at';

        if ($m->declares('edit')) {
            $properties[] = ' * @property int $lock_version';
            $casts[] = "'lock_version' => 'integer',";
        }

        if ($m->hasFiles()) {
            array_push($body, '', '    /**', '     * @return array<string, AttachmentSlot>', '     */', '    public function attachmentSlots(): array', '    {', '        return [');

            foreach ($m->fieldsOf(FieldType::File) as $field) {
                $slot = 'AttachmentSlot::'.($field->multiple ? 'multiple' : 'single')."(__('{$field->label()}'))"
                    .($field->required ? '->required()' : '')
                    .($field->mimes !== [] ? '->mimes(['.$this->list($field->mimes).'])' : '')
                    .($field->maxKb !== null ? "->maxKb({$field->maxKb})" : '');
                $body[] = "            '{$field->name}' => {$slot},";
            }

            array_push($body, '        ];', '    }');
        }

        $label = $m->labelField();

        if ($label !== null && ! in_array($label->name, ['name', 'title', 'number'], true)) {
            array_push($body, '', '    public function auditLabel(): string', '    {', '        return $this->'.$label->name.($label->nullable ? " ?? '#'.\$this->id" : '').';', '    }');
        }

        $body[] = '';
        $filters = array_map(fn (FieldDefinition $field): string => "new DataTableFilterData('{$field->name}', __('{$field->label()}'), {$this->enumClass($field)}::options())", $m->fieldsOf(FieldType::Enum));

        if ($filters !== []) {
            $imports['Yuan\AdminCore\Data\DataTableFilterData'] = true;
        }

        return $this->render('module/model', [
            'imports' => $this->phpImports($imports),
            'properties' => implode("\n", $properties),
            'traits' => implode(', ', $traits),
            'body' => implode("\n", $body),
            'search' => $this->list(array_map(fn (FieldDefinition $field): string => $field->name, array_values(array_filter($m->columns(), fn (FieldDefinition $field): bool => $field->type->searchable())))),
            'sorts' => $this->list(array_map(fn (FieldDefinition $field): string => $field->name, array_values(array_filter($m->columns(), fn (FieldDefinition $field): bool => $field->type->sortable())))),
            'filters' => implode(', ', $filters),
            'casts' => $this->lines($casts, 12),
        ]);
    }

    private function policy(): string
    {
        $m = $this->module;
        $methods = [];
        $abilities = ['view' => ['viewAny' => false, 'view' => true], 'create' => ['create' => false], 'edit' => ['update' => true], 'delete' => ['delete' => true], 'download' => ['download' => false]];

        foreach ($abilities as $action => $map) {
            if (! $m->declares($action)) {
                continue;
            }

            foreach ($map as $method => $record) {
                $signature = 'Authorizable $user'.($record ? ", {$m->name} \${$m->variable()}" : '');
                $methods[] = "    public function {$method}({$signature}): bool\n    {\n        return ".($m->superadminOnly ? 'false' : "\$user->can('{$m->slug}.{$action}')").";\n    }";
            }
        }

        return $this->render('module/policy', [
            'methods' => implode("\n\n", $methods),
            'docblock' => $m->superadminOnly ? '/** A superadmin_only module: Super Admin passes Gate::before, everyone else is refused. */' : '',
        ]);
    }

    /**
     * @param  list<FieldDefinition|null>  $fields  null stands for lock_version.
     */
    private function outputData(string $class, array $fields, string $doc): string
    {
        $m = $this->module;
        $imports = ['Spatie\LaravelData\Data' => true, 'Spatie\TypeScriptTransformer\Attributes\TypeScript' => true, "Modules\\{$m->name}\\Models\\{$m->name}" => true];
        $properties = ['public int $id,'];
        $values = ["id: \${$m->variable()}->id,"];
        $model = "\${$m->variable()}";

        foreach ($fields as $field) {
            if ($field === null) {
                $properties[] = 'public int $lock_version,';
                $values[] = "lock_version: {$model}->lock_version,";

                continue;
            }

            $n = $field->name;
            $q = $field->nullable ? '?' : '';
            $access = "{$model}->{$n}".($field->nullable ? '?' : '');

            switch ($field->type) {
                case FieldType::Enum:
                    $imports["Modules\\{$m->name}\\Enums\\{$this->enumClass($field)}"] = true;
                    $properties[] = "public {$q}{$this->enumClass($field)} \${$n},";
                    $properties[] = "public {$q}string \${$n}_label,";
                    $values[] = "{$n}: {$model}->{$n},";
                    $values[] = "{$n}_label: {$access}->label(),";
                    break;
                case FieldType::Foreign:
                    $properties[] = "public {$q}int \${$n},";
                    $properties[] = "public {$q}string \${$field->relationLabel()},";
                    $values[] = "{$n}: {$model}->{$n},";
                    $values[] = "{$field->relationLabel()}: {$model}->{$field->relation()}".($field->nullable ? '?' : '').'->auditLabel(),';
                    break;
                default:
                    [$type, $value] = match ($field->type) {
                        FieldType::Integer, FieldType::Year, FieldType::Currency => ['int', "{$model}->{$n}"],
                        FieldType::Decimal => ['float', $field->nullable ? "{$model}->{$n} === null ? null : (float) {$model}->{$n}" : "(float) {$model}->{$n}"],
                        FieldType::Boolean => ['bool', "{$model}->{$n}"],
                        FieldType::Date => ['string', "{$access}->toDateString()"],
                        FieldType::DateTime => ['string', "{$access}->format('Y-m-d H:i')"],
                        default => ['string', "{$model}->{$n}"],
                    };
                    $properties[] = "public {$q}{$type} \${$n},";
                    $values[] = "{$n}: {$value},";
            }
        }

        return $this->render('module/data', [
            'imports' => $this->phpImports($imports),
            'class' => $class,
            'doc' => $doc,
            'properties' => $this->lines($properties, 8),
            'values' => $this->lines($values, 12),
        ]);
    }

    private function inputData(bool $update): string
    {
        $m = $this->module;
        $class = ($update ? 'Update' : 'Store')."{$m->name}Data";
        $imports = ['Spatie\LaravelData\Data' => true, 'Spatie\TypeScriptTransformer\Attributes\TypeScript' => true];
        $properties = [];
        $rules = [];
        $attributes = [];

        foreach ($m->columns() as $field) {
            $n = $field->name;
            $q = $field->nullable && $field->type !== FieldType::Boolean ? '?' : '';
            $presence = $field->type === FieldType::Boolean ? "'boolean'" : ($field->nullable ? "'nullable'" : "'required'");
            [$type, $rule] = match ($field->type) {
                FieldType::String => ['string', "'string', 'max:255'"],
                FieldType::Text => ['string', "'string', 'max:5000'"],
                FieldType::Integer => ['int', "'integer'"],
                FieldType::Year => ['int', "'integer', 'between:1900,2100'"],
                FieldType::Decimal => ['float', "'numeric'"],
                FieldType::Currency => ['int', "'integer', 'min:0'"],
                FieldType::Boolean => ['bool', null],
                FieldType::Date => ['string', "'date_format:Y-m-d'"],
                FieldType::DateTime => ['string', "'date_format:Y-m-d H:i'"],
                FieldType::Enum => [$this->enumClass($field), "Rule::enum({$this->enumClass($field)}::class)"],
                FieldType::Foreign => ['int', "'integer', new VisibleRecord({$this->target($field)->shortName()}::class)"],
                FieldType::File => ['', null],
            };

            if ($field->type === FieldType::Enum) {
                $imports['Illuminate\Validation\Rule'] = true;
                $imports["Modules\\{$m->name}\\Enums\\{$this->enumClass($field)}"] = true;
            }

            if ($field->type === FieldType::Foreign) {
                $imports['Yuan\AdminCore\Validation\VisibleRecord'] = true;
                $imports[$this->target($field)->model] = true;
            }

            $properties[] = "public {$q}{$type} \${$n},";
            $rules[] = "'{$n}' => [{$presence}".($rule !== null ? ", {$rule}" : '').'],';
            $attributes[] = "'{$n}' => __('".Str::lower($field->label())."'),";
        }

        if ($update) {
            $properties[] = 'public int $lock_version,';
            $rules[] = "'lock_version' => ['required', 'integer', 'min:0'],";
        }

        $constructorDoc = '';

        if ($m->hasFiles()) {
            $imports['Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType'] = true;
            $imports['Yuan\AdminCore\Attachments\AttachmentUploads'] = true;
            $imports["Modules\\{$m->name}\\Models\\{$m->name}"] = true;
            $properties[] = "#[LiteralTypeScriptType('Record<string, File | File[] | null>')]";
            $properties[] = 'public array $files = [],';
            $rules[] = $update
                ? "...AttachmentUploads::rules({$m->name}::class, is_numeric(\$id = request()->route('{$m->variable()}')) ? {$m->name}::query()->find((int) \$id) : null),"
                : "...AttachmentUploads::rules({$m->name}::class),";
            $attributes[] = "...AttachmentUploads::attributes({$m->name}::class),";
            $constructorDoc = "    /**\n     * @param  array<string, mixed>  \$files  Attachment slot => an upload, or a list of uploads (`attachmentSlots()`).\n     */\n";
        }

        return $this->render('module/input-data', [
            'imports' => $this->phpImports($imports),
            'class' => $class,
            'doc' => $update ? 'The edit form; `lock_version` is the version it loaded (PreventsStaleUpdates).' : "The create form of a {$this->lower()} record.",
            'constructor_doc' => $constructorDoc,
            'properties' => $this->lines($properties, 8),
            'rules' => $this->lines($rules, 12),
            'attributes' => $this->lines($attributes, 12),
        ]);
    }

    private function formOptions(): string
    {
        $m = $this->module;
        $imports = ['Spatie\LaravelData\Data' => true, 'Spatie\TypeScriptTransformer\Attributes\TypeScript' => true, 'Yuan\AdminCore\Data\OptionData' => true];
        $docs = [];
        $properties = [];
        $values = [];

        foreach ($m->fieldsOf(FieldType::Enum) as $field) {
            $imports["Modules\\{$m->name}\\Enums\\{$this->enumClass($field)}"] = true;
            $docs[] = "     * @param  list<OptionData>  \${$field->name}_options";
            $properties[] = "        public array \${$field->name}_options,";
            $values[] = "{$field->name}_options: {$this->enumClass($field)}::options()";
        }

        return $this->render('module/form-options-data', [
            'imports' => $this->phpImports($imports),
            'param_docs' => implode("\n", $docs),
            'properties' => implode("\n", $properties),
            'values' => implode(', ', $values),
        ]);
    }

    private function enum(FieldDefinition $field): string
    {
        return $this->render('module/enum', [
            'Enum' => $this->enumClass($field),
            'cases' => $this->lines(array_map(fn (string $value): string => 'case '.FieldDefinition::enumCase($value)." = '{$value}';", $field->values), 4),
            'labels' => $this->lines(array_map(fn (string $value): string => 'self::'.FieldDefinition::enumCase($value)." => __('".Str::ucfirst(Str::lower(Str::headline($value)))."'),", $field->values), 12),
        ]);
    }

    private function export(): string
    {
        $m = $this->module;
        $imports = ['Illuminate\Database\Eloquent\Model' => true, "Modules\\{$m->name}\\Models\\{$m->name}" => true, 'Yuan\AdminCore\DataTable\DataTableBuilder' => true, 'Yuan\AdminCore\Exports\ModuleExport' => true];
        $headings = [];
        $cells = [];

        foreach ($m->columns() as $field) {
            if (! $field->type->listed()) {
                continue;
            }

            $n = $field->name;
            $access = "\$row->{$n}".($field->nullable ? '?' : '');
            $headings[] = "__('{$field->label()}')";
            $cells[] = match ($field->type) {
                FieldType::Decimal => $field->nullable ? "\$row->{$n} === null ? null : (float) \$row->{$n}," : "(float) \$row->{$n},",
                FieldType::Date => "{$access}->toDateString(),",
                FieldType::DateTime => "{$access}->format('Y-m-d H:i'),",
                FieldType::Enum => "{$access}->label(),",
                FieldType::Foreign => "\$row->{$field->relation()}".($field->nullable ? '?' : '').'->auditLabel(),',
                default => "\$row->{$n},",
            };
        }

        return $this->render('module/export', [
            'imports' => $this->phpImports($imports),
            'with' => $this->with(),
            'headings' => implode(', ', $headings),
            'cells' => $this->lines($cells, 12),
        ]);
    }

    /** `->with('customer')` for the foreign fields, or nothing. */
    private function with(): string
    {
        $relations = array_map(fn (FieldDefinition $field): string => $field->relation(), $this->module->fieldsOf(FieldType::Foreign));

        return $relations === [] ? '' : '->with('.$this->list($relations).')';
    }

    /**
     * @return list<string>
     */
    private function migrationColumns(): array
    {
        $lines = [];

        foreach ($this->module->columns() as $field) {
            $n = $field->name;
            $nullable = $field->nullable ? '->nullable()' : '';
            $index = $field->type->sortable() || $field->type === FieldType::Enum ? '->index()' : '';
            $lines[] = '$table->'.match ($field->type) {
                FieldType::String => "string('{$n}'){$nullable}{$index};",
                FieldType::Text => "text('{$n}'){$nullable};",
                FieldType::Integer => "integer('{$n}'){$nullable}{$index};",
                FieldType::Year => "unsignedSmallInteger('{$n}'){$nullable}{$index};",
                FieldType::Decimal => "decimal('{$n}', 15, 3){$nullable}{$index};",
                FieldType::Currency => "unsignedBigInteger('{$n}'){$nullable}{$index};",
                FieldType::Boolean => "boolean('{$n}')->default(false);",
                FieldType::Date => "date('{$n}'){$nullable}{$index};",
                FieldType::DateTime => "dateTime('{$n}'){$nullable}{$index};",
                FieldType::Enum => "string('{$n}', 32){$nullable}{$index};",
                FieldType::Foreign => "foreignId('{$n}'){$nullable}->constrained('{$field->table}')->restrictOnDelete();",
                FieldType::File => '',
            };
        }

        if ($this->module->declares('edit')) {
            $lines[] = "\$table->unsignedInteger('lock_version')->default(0);";
        }

        return $lines;
    }

    private function factory(): string
    {
        $m = $this->module;
        $imports = ['Illuminate\Database\Eloquent\Factories\Factory' => true, "Modules\\{$m->name}\\Models\\{$m->name}" => true];
        $values = [];

        foreach ($m->columns() as $field) {
            $value = match ($field->type) {
                FieldType::String => 'fake()->words(3, true)',
                FieldType::Text => 'fake()->sentence()',
                FieldType::Integer => 'fake()->numberBetween(1, 1000)',
                FieldType::Year => 'fake()->numberBetween(1990, now()->year)',
                FieldType::Decimal => 'fake()->randomFloat(3, 0, 1000)',
                FieldType::Currency => 'fake()->numberBetween(1, 10_000) * 1000',
                FieldType::Boolean => 'fake()->boolean()',
                FieldType::Date => "fake()->date('Y-m-d')",
                FieldType::DateTime => "fake()->dateTime()->format('Y-m-d H:i:00')",
                FieldType::Enum => "fake()->randomElement({$this->enumClass($field)}::cases())",
                FieldType::Foreign => "{$this->target($field)->shortName()}::factory()",
                FieldType::File => 'null',
            };

            if ($field->type === FieldType::Enum) {
                $imports["Modules\\{$m->name}\\Enums\\{$this->enumClass($field)}"] = true;
            }

            if ($field->type === FieldType::Foreign) {
                $imports[$this->target($field)->model] = true;
            }

            $values[] = "'{$field->name}' => {$value},";
        }

        return $this->render('module/factory', ['imports' => $this->phpImports($imports), 'values' => $this->lines($values, 12)]);
    }

    private function routes(): string
    {
        $m = $this->module;
        $controller = "{$m->name}Controller";
        $imports = ['Illuminate\Support\Facades\Route' => true, "Modules\\{$m->name}\\Http\\Controllers\\{$controller}" => true];
        $permission = fn (string $action): string => $m->superadminOnly ? '' : "->middleware('permission:{$m->slug}.{$action}')";
        $lines = [];

        if ($m->superadminOnly) {
            $lines[] = '// A superadmin_only module: the registry adds the `superadmin` middleware.';
        }

        if ($m->declares('delete')) {
            $imports["Modules\\{$m->name}\\Models\\{$m->name}"] = true;
            $imports['Yuan\AdminCore\Trash\TrashController'] = true;
            $lines[] = "TrashController::routes({$m->name}::class);";
        }

        if ($m->hasFiles()) {
            $imports["Modules\\{$m->name}\\Models\\{$m->name}"] = true;
            $imports['Yuan\AdminCore\Attachments\AttachmentController'] = true;
            $lines[] = "AttachmentController::routes({$m->name}::class);";
        }

        if ($lines !== []) {
            $lines[] = '';
        }

        $lines[] = "Route::get('/', [{$controller}::class, 'index']){$permission('view')}->name('index');";
        $lines[] = "Route::get('lookup', [{$controller}::class, 'lookup']){$permission('view')}->name('lookup');";

        if ($m->declares('download')) {
            $lines[] = "Route::get('download', [{$controller}::class, 'download']){$permission('download')}->name('download');";
        }

        if ($m->declares('create')) {
            $lines[] = "Route::get('create', [{$controller}::class, 'create']){$permission('create')}->name('create');";
            $lines[] = "Route::post('/', [{$controller}::class, 'store']){$permission('create')}->name('store');";
        }

        if ($m->declares('delete')) {
            $lines[] = "Route::delete('/', [{$controller}::class, 'bulkDestroy']){$permission('delete')}->name('bulk-destroy');";
        }

        $v = $m->variable();
        $lines[] = '';
        $lines[] = "Route::whereNumber('{$v}')->group(function (): void {";
        $lines[] = "    Route::get('{{$v}}', [{$controller}::class, 'show']){$permission('view')}->name('show');";

        if ($m->declares('edit')) {
            $lines[] = "    Route::get('{{$v}}/edit', [{$controller}::class, 'edit']){$permission('edit')}->name('edit');";
            $lines[] = "    Route::put('{{$v}}', [{$controller}::class, 'update']){$permission('edit')}->name('update');";
        }

        if ($m->declares('delete')) {
            $lines[] = "    Route::delete('{{$v}}', [{$controller}::class, 'destroy']){$permission('delete')}->name('destroy');";
        }

        $lines[] = '});';

        return $this->render('module/routes', ['imports' => $this->phpImports($imports), 'routes' => implode("\n", $lines)]);
    }

    private function controller(): string
    {
        $m = $this->module;
        $imports = [
            'Illuminate\Http\Request' => true,
            'Illuminate\Support\Facades\Gate' => true,
            'Inertia\Inertia' => true,
            'Inertia\Response' => true,
            "Modules\\{$m->name}\\Data\\{$m->name}Data" => true,
            "Modules\\{$m->name}\\Data\\{$m->name}ListItemData" => true,
            "Modules\\{$m->name}\\Models\\{$m->name}" => true,
            'Yuan\AdminCore\Audit\ActivityHistory' => true,
            'Yuan\AdminCore\Data\BreadcrumbData' => true,
            'Yuan\AdminCore\Data\BreadcrumbsData' => true,
            'Yuan\AdminCore\Data\LookupData' => true,
            'Yuan\AdminCore\Data\OptionData' => true,
            'Yuan\AdminCore\Data\PaginatedData' => true,
            'Yuan\AdminCore\DataTable\DataTableQuery' => true,
        ];
        $formProps = fn (string $record): string => ($m->hasOptions() ? "            'options' => {$m->name}FormOptionsData::make(),\n" : '')
            .($m->hasFiles() ? "            'attachments' => AttachmentController::data({$record}, \$request->user()),\n" : '');
        $values = [
            'with' => $this->with(),
            'show_props' => $m->hasFiles() ? "            'attachments' => AttachmentController::data(\$model, \$request->user()),\n" : '',
            'form_props_new' => $formProps("new {$m->name}"),
            'form_props_model' => $formProps('$model'),
            'store' => $m->hasFiles()
                ? "AttachmentUploads::saveWith(fn (): {$m->name} => {$m->name}::query()->create(\$data->except('files')->toArray()), \$data->files, \$request->user())"
                : "{$m->name}::query()->create(\$data->toArray())",
            'update' => $m->hasFiles()
                ? "AttachmentUploads::saveWith(function () use (\$model, \$data): {$m->name} {\n            \$model->updateUnlessStale(\$data->except('lock_version', 'files')->toArray(), \$data->lock_version);\n\n            return \$model;\n        }, \$data->files, \$request->user());"
                : "\$model->updateUnlessStale(\$data->except('lock_version')->toArray(), \$data->lock_version);",
        ];
        $methods = [$this->render('module/fragments/controller.index', $values), $this->render('module/fragments/controller.show', $values)];

        if ($m->hasFiles()) {
            $imports['Yuan\AdminCore\Attachments\AttachmentController'] = true;
        }

        if ($m->declares('download')) {
            $imports['Symfony\Component\HttpFoundation\Response as SymfonyResponse'] = true;
            $imports["Modules\\{$m->name}\\Exports\\{$m->name}Export"] = true;
            $imports['Yuan\AdminCore\Exports\ExportManager'] = true;
            $methods[] = $this->render('module/fragments/controller.download', $values);
        }

        foreach (['create' => 'Store', 'edit' => 'Update'] as $action => $prefix) {
            if (! $m->declares($action)) {
                continue;
            }

            $imports['Illuminate\Http\RedirectResponse'] = true;
            $imports['Yuan\AdminCore\Data\FlashData'] = true;
            $imports["Modules\\{$m->name}\\Data\\{$prefix}{$m->name}Data"] = true;

            if ($m->hasOptions()) {
                $imports["Modules\\{$m->name}\\Data\\{$m->name}FormOptionsData"] = true;
            }

            if ($m->hasFiles()) {
                $imports['Yuan\AdminCore\Attachments\AttachmentUploads'] = true;
            }

            $methods[] = $this->render("module/fragments/controller.{$action}", $values);
        }

        if ($m->declares('delete')) {
            $imports['Illuminate\Http\RedirectResponse'] = true;
            $imports['Yuan\AdminCore\Data\SelectionData'] = true;
            $imports['Yuan\AdminCore\Trash\Trash'] = true;
            $methods[] = $this->render('module/fragments/controller.delete', $values);
        }

        return $this->render('module/controller', ['imports' => $this->phpImports($imports), 'methods' => rtrim(implode("\n", $methods))]);
    }

    // ---- Tests -------------------------------------------------------------

    private function tests(): string
    {
        $m = $this->module;
        $slug = $m->slug;
        $base = $this->base();
        $v = $m->variable();
        $inputFunction = Str::camel($m->name).'Input';
        $imports = [
            'Illuminate\Foundation\Testing\RefreshDatabase' => true,
            'Inertia\Testing\AssertableInertia' => true,
            "Modules\\{$m->name}\\Models\\{$m->name}" => true,
            'Yuan\AdminCore\Testing\ModuleAssertions' => true,
            'function Pest\Laravel\get' => true,
        ];
        $acting = fn (string ...$actions): string => $m->superadminOnly
            ? 'ModuleAssertions::actingAsSuperAdmin()'
            : 'ModuleAssertions::actingAsLevelWith(['.$this->list(array_map(fn (string $action): string => "{$slug}.{$action}", array_values(array_unique(['view', ...$actions])))).'])';
        $masked = $m->maskedFields() === [] ? '' : ', masked: ['.$this->list($m->maskedFields()).']';
        $guest = [['get', "'{$base}'"], ['get', "\"{$base}/{\${$v}->id}\""]];
        $forbidden = [['view', 'get', "'{$base}'", ''], ['view', 'get', "\"{$base}/{\${$v}->id}\"", '']];
        $scoped = [['get', "'{$base}'", ''], ['get', "\"{$base}/{\${$v}->id}\"", '']];

        if ($m->declares('create')) {
            $guest[] = ['get', "'{$base}/create'"];
            $guest[] = ['post', "'{$base}'"];
            $forbidden[] = ['create', 'get', "'{$base}/create'", ''];
            $forbidden[] = ['create', 'post', "'{$base}'", ", {$inputFunction}()"];
        }

        if ($m->declares('edit')) {
            $guest[] = ['get', "\"{$base}/{\${$v}->id}/edit\""];
            $guest[] = ['put', "\"{$base}/{\${$v}->id}\""];
            $forbidden[] = ['edit', 'get', "\"{$base}/{\${$v}->id}/edit\"", ''];
            $forbidden[] = ['edit', 'put', "\"{$base}/{\${$v}->id}\"", ", {$inputFunction}(['lock_version' => \${$v}->lock_version])"];
            $scoped[] = ['get', "\"{$base}/{\${$v}->id}/edit\"", ''];
            $scoped[] = ['put', "\"{$base}/{\${$v}->id}\"", ", {$inputFunction}(['lock_version' => \${$v}->lock_version])"];
        }

        if ($m->declares('delete')) {
            $guest[] = ['delete', "\"{$base}/{\${$v}->id}\""];
            $guest[] = ['delete', "'{$base}'"];
            $forbidden[] = ['delete', 'delete', "\"{$base}/{\${$v}->id}\"", ''];
            $forbidden[] = ['delete', 'delete', "'{$base}'", ", ['ids' => [\${$v}->id]]"];
            $scoped[] = ['delete', "'{$base}'", ", ['ids' => [\${$v}->id]]"];
            $scoped[] = ['delete', "\"{$base}/{\${$v}->id}\"", ''];
        }

        if ($m->declares('download')) {
            $guest[] = ['get', "'{$base}/download'"];
            $forbidden[] = ['download', 'get', "'{$base}/download'", ''];
        }

        $forbiddenTest = $m->superadminOnly
            ? "it('refuses every other level, even one holding every permission there is', function (): void {\n    \${$v} = {$m->name}::factory()->create();\n    ModuleAssertions::actingAsLevelWith(['users.view', 'users.create', 'users.edit', 'users.delete', 'users.download']);\n\n    get('{$base}')->assertForbidden();\n    get(\"{$base}/{\${$v}->id}\")->assertForbidden();\n});"
            : "it('needs the permission of each action', function (): void {\n    \${$v} = {$m->name}::factory()->create();\n\n"
                .implode("\n", array_map(fn (array $row): string => "    ModuleAssertions::assertForbiddenWithout('{$slug}.{$row[0]}', '{$row[1]}', {$row[2]}{$row[3]});", $forbidden))
                ."\n\n    expect(\${$v}->fresh()?->trashed())->toBeFalse();\n});";
        $values = [
            'base' => $base,
            'input_fn' => $inputFunction,
            'masked' => $masked,
            'guest_routes' => implode("\n", array_map(fn (array $row): string => "    ModuleAssertions::assertGuestRedirected('{$row[0]}', {$row[1]});", $guest)),
            'forbidden_test' => $forbiddenTest,
            'scope_routes' => implode("\n", array_map(fn (array $row): string => "    ModuleAssertions::assertScopedToUser({$m->name}::class, '{$row[0]}', {$row[1]}{$row[2]});", $scoped)),
            'acting_view' => $acting(),
            'acting_all' => $acting(...$m->actions),
            'acting_create' => $acting('create'),
            'acting_edit' => $acting('edit'),
            'acting_delete' => $acting('delete'),
            'acting_download' => $acting('download'),
            'required' => $this->list(array_map(fn (FieldDefinition $field): string => $field->name, array_values(array_filter($m->columns(), fn (FieldDefinition $field): bool => ! $field->nullable && $field->type !== FieldType::Boolean)))),
        ];
        $tests = [$this->render('module/fragments/test.view', $values)];

        if ($m->declares('create')) {
            $imports['function Pest\Laravel\post'] = true;
            $tests[] = $this->render('module/fragments/test.create', $values);

            foreach ($m->fieldsOf(FieldType::Foreign) as $field) {
                $target = $this->target($field);
                $imports[$target->model] = true;
                $tests[] = $this->render('module/fragments/test.foreign', [...$values, 'Target' => $target->shortName(), 'target_label' => Str::lower($field->label()), 'field' => $field->name]);
            }
        }

        if ($m->declares('edit')) {
            $imports['function Pest\Laravel\put'] = true;
            $tests[] = $this->render('module/fragments/test.edit', $values);
        }

        if ($m->declares('delete')) {
            $imports['function Pest\Laravel\delete'] = true;
            $imports['function Pest\Laravel\post'] = true;
            $imports['Yuan\AdminCore\Audit\ImmutableActivity'] = true;
            $tests[] = $this->render('module/fragments/test.delete', $values);

            if (! $m->superadminOnly) {
                $tests[] = $this->render('module/fragments/test.trash', $values);
            }
        }

        if ($m->declares('download')) {
            $imports['Yuan\AdminCore\Audit\ImmutableActivity'] = true;
            $tests[] = $this->render('module/fragments/test.download', $values);
        }

        $classes = array_filter(array_keys($imports), fn (string $import): bool => ! str_starts_with($import, 'function '));
        $functions = array_map(fn (string $import): string => substr($import, 9), array_values(array_filter(array_keys($imports), fn (string $import): bool => str_starts_with($import, 'function '))));
        sort($classes);
        sort($functions);

        if (array_filter($m->fieldsOf(FieldType::File), fn (FieldDefinition $field): bool => $field->required) !== [] && $this->hasForm()) {
            $classes[] = 'Illuminate\Http\UploadedFile';
            sort($classes);
        }

        return $this->render('module/test', [
            'imports' => implode("\n", array_map(fn (string $class): string => "use {$class};", $classes))."\n\n".implode("\n", array_map(fn (string $function): string => "use function {$function};", $functions)),
            'input_function' => $this->hasForm() ? $this->inputFunction($inputFunction) : '',
            'tests' => implode("\n", $tests),
        ]);
    }

    /** A valid form submission, as the create page sends it. */
    private function inputFunction(string $name): string
    {
        $values = [];

        foreach ($this->module->columns() as $field) {
            $value = match ($field->type) {
                FieldType::String => "'Contoh {$field->label()}'",
                FieldType::Text => "'Catatan contoh'",
                FieldType::Integer => '12',
                FieldType::Year => '2024',
                FieldType::Decimal => '2.5',
                FieldType::Currency => '150000',
                FieldType::Boolean => 'true',
                FieldType::Date => "'2026-10-20'",
                FieldType::DateTime => "'2026-10-20 09:30'",
                FieldType::Enum => "'{$field->values[0]}'",
                FieldType::Foreign => "{$this->target($field)->shortName()}::factory()->create()->id",
                FieldType::File => 'null',
            };
            $values[] = "        '{$field->name}' => {$value},";
        }

        // A required attachment slot needs a file in every submission.
        $files = [];

        foreach ($this->module->fieldsOf(FieldType::File) as $field) {
            if ($field->required) {
                $file = "UploadedFile::fake()->create('berkas.".($field->mimes[0] ?? 'pdf')."', 10)";
                $files[] = "'{$field->name}' => ".($field->multiple ? "[{$file}]" : $file);
            }
        }

        if ($files !== []) {
            $values[] = "        'files' => [".implode(', ', $files).'],';
        }

        return "/**\n * A valid form submission, as the create page sends it.\n *\n * @param  array<string, mixed>  \$overrides\n * @return array<string, mixed>\n */\nfunction {$name}(array \$overrides = []): array\n{\n    return [\n".implode("\n", $values)."\n        ...\$overrides,\n    ];\n}\n";
    }

    // ---- TSX ---------------------------------------------------------------

    /** A TSX expression for the record's label: `row.name`, or `#12` when there is no string field. */
    private function labelExpression(string $record): string
    {
        $label = $this->module->labelField();

        if ($label === null) {
            return "`#\${{$record}.id}`";
        }

        return $label->nullable ? "({$record}.{$label->name} ?? `#\${{$record}.id}`)" : "{$record}.{$label->name}";
    }

    /** The PageHeader `actions` prop line, or nothing when there are none. */
    private function headerActions(string $actions): string
    {
        return $actions === '' ? '' : "                    actions={<>{$actions}</>}\n";
    }

    private function iconComponent(): string
    {
        return Str::studly($this->module->icon);
    }

    private function indexPage(): string
    {
        $m = $this->module;
        $slug = $m->slug;
        $imports = [
            '@admin-core/components/admin/DataTable' => ['DataTable' => true, 'type:DataTableColumn' => true, 'type:RowAction' => true],
            '@admin-core/components/admin/PageHeader' => ['PageHeader' => true],
            '@admin-core/hooks/usePermission' => ['usePermission' => true],
            '@admin-core/lib/i18n' => ['useT' => true],
            '@inertiajs/react' => ['Head' => true],
            'lucide-react' => ['Eye' => true, $this->iconComponent() => true],
            "@/routes/{$slug}" => ['show' => true],
        ];
        $columns = [];

        foreach ($m->columns() as $field) {
            if (! $field->type->listed()) {
                continue;
            }

            $key = $field->type === FieldType::Foreign ? $field->relationLabel() : $field->name;
            $parts = ["key: '{$key}'", "label: t('{$slug}.{$field->name}')"];
            $value = "row.{$field->name}";
            $cell = match ($field->type) {
                FieldType::Integer => "formatNumber({$value})",
                FieldType::Decimal => "formatNumber({$value}, 3)",
                FieldType::Currency => "formatCurrency({$value})",
                FieldType::Date => "formatLocalDate({$value})",
                FieldType::DateTime => "formatLocalDateTime({$value})",
                FieldType::Enum => "<StatusBadge label={row.{$field->name}_label} />",
                default => null,
            };

            if ($cell !== null && $field->nullable && $field->type !== FieldType::Enum) {
                $cell = "{$value} === null ? '—' : {$cell}";
            } elseif ($cell !== null && $field->nullable) {
                $cell = "row.{$field->name}_label && {$cell}";
            }

            if (in_array($field->type, [FieldType::Integer, FieldType::Decimal, FieldType::Currency], true)) {
                $parts[] = "align: 'right'";
            }

            if ($field === $m->labelField()) {
                $parts[] = "className: 'font-medium'";
                $parts[] = 'hideable: false';
            }

            if ($cell !== null) {
                $parts[] = "cell: (row) => {$cell}";
                $this->formatImport($imports, $field);
            }

            $columns[] = '        { '.implode(', ', $parts).' },';
        }

        $rowActions = ["        ...({$this->can('view')} ? [{ label: t('table.view'), icon: Eye, href: show(row.id) }] : []),"];
        $hooks = '';
        $createButton = '';
        $headerActions = '';
        $tableProps = [];
        $emptyAction = '';
        $deleteDialog = '';

        if ($m->declares('edit')) {
            $imports["@/routes/{$slug}"]['edit'] = true;
            $imports['lucide-react']['Pencil'] = true;
            $rowActions[] = "        ...({$this->can('edit')} ? [{ label: t('table.edit'), icon: Pencil, href: edit(row.id) }] : []),";
        }

        if ($m->declares('delete')) {
            $imports["@/routes/{$slug}"] += ['bulkDestroy' => true, 'destroy' => true, 'trash' => true];
            $imports['lucide-react']['Trash2'] = true;
            $imports['@admin-core/components/admin/ConfirmDialog'] = ['ConfirmDialog' => true];
            $imports['@admin-core/hooks/useAction'] = ['useAction' => true];
            $imports['@inertiajs/react'] += ['Link' => true, 'router' => true];
            $imports['@admin-core/components/ui/button'] = ['Button' => true];
            $imports['react'] = ['useState' => true];
            $hooks = "    const removing = useAction();\n    const [deleting, setDeleting] = useState<Row | null>(null);\n";
            $rowActions[] = "        ...({$this->can('delete')} ? [{ label: t('table.delete'), icon: Trash2, destructive: true, onSelect: () => setDeleting(row) }] : []),";
            $headerActions .= "{isSuperAdmin && (<Button variant=\"outline\" asChild className=\"bg-transparent\"><Link href={trash()}><Trash2 />{t('trash.link')}</Link></Button>)}";
            $tableProps[] = "                    bulkDelete={{$this->can('delete')} ? (ids, onFinish) => router.delete(bulkDestroy(), { onFinish, data: { ids }, preserveScroll: true }) : undefined}";
            $deleteDialog = "            <ConfirmDialog\n                open={deleting !== null}\n                onOpenChange={(open) => !open && setDeleting(null)}\n                title={t('{$slug}.delete_title')}\n                itemName={deleting ? {$this->labelExpression('deleting')} : ''}\n                loading={removing.pending}\n                onConfirm={() => {\n                    if (deleting) {\n                        removing.run((done) =>\n                            router.delete(destroy(deleting.id), {\n                                preserveScroll: true,\n                                onFinish: () => {\n                                    done();\n                                    setDeleting(null);\n                                },\n                            }),\n                        );\n                    }\n                }}\n            />";
        }

        if ($m->declares('create')) {
            $imports["@/routes/{$slug}"]['create'] = true;
            $imports['lucide-react']['Plus'] = true;
            $imports['@inertiajs/react']['Link'] = true;
            $imports['@admin-core/components/ui/button'] = ['Button' => true];
            $createButton = "\n    const createButton = {$this->can('create')} && (\n        <Button asChild>\n            <Link href={create()}>\n                <Plus />\n                {t('table.create')}\n            </Link>\n        </Button>\n    );\n";
            $headerActions .= '{createButton}';
            $emptyAction = '                        action: createButton,';
        }

        if ($m->declares('download')) {
            $imports["@/routes/{$slug}"]['download'] = true;
            $tableProps[] = "                    download={{$this->can('download')} ? download() : undefined}";
        }

        $imports = $this->sortTsImports($imports);
        $permissions = array_values(array_filter([$m->superadminOnly ? null : 'can', $m->superadminOnly || $m->declares('delete') ? 'isSuperAdmin' : null]));

        return $this->render('module/js/Index', [
            'permissions' => implode(', ', $permissions),
            'imports' => $this->tsImports($imports),
            'hooks' => $hooks,
            'columns' => implode("\n", $columns),
            'row_actions' => implode("\n", $rowActions),
            'create_button' => $createButton,
            'header_actions' => $this->headerActions($headerActions),
            'row_label' => $this->labelExpression('row'),
            'can_view' => $this->can('view'),
            'table_props' => implode("\n", $tableProps),
            'Icon' => $this->iconComponent(),
            'empty_action' => $emptyAction,
            'delete_dialog' => $deleteDialog,
        ]);
    }

    private function showPage(): string
    {
        $m = $this->module;
        $slug = $m->slug;
        $v = $m->variable();
        $imports = [
            '@admin-core/components/admin/ActivityHistory' => ['ActivityHistory' => true],
            '@admin-core/components/admin/DetailGrid' => ['DetailGrid' => true],
            '@admin-core/components/admin/PageHeader' => ['PageHeader' => true],
            '@admin-core/components/admin/Tabs' => ['Tabs' => true],
            '@admin-core/hooks/usePermission' => ['usePermission' => true],
            '@admin-core/lib/i18n' => ['useT' => true],
            '@inertiajs/react' => ['Head' => true],
            'react' => ['useState' => true],
            "@/routes/{$slug}" => ['index' => true],
        ];
        $details = [];

        foreach ($m->columns() as $field) {
            $value = "{$v}.{$field->name}";
            $shown = match ($field->type) {
                FieldType::Integer => "formatNumber({$value})",
                FieldType::Decimal => "formatNumber({$value}, 3)",
                FieldType::Currency => "formatCurrency({$value})",
                FieldType::Date => "formatLocalDate({$value})",
                FieldType::DateTime => "formatLocalDateTime({$value})",
                FieldType::Boolean => "{$value} ? t('{$slug}.yes') : t('{$slug}.no')",
                FieldType::Enum => "<StatusBadge label={{$v}.{$field->name}_label} />",
                FieldType::Foreign => "{$v}.{$field->relationLabel()}",
                default => $value,
            };

            if ($field->nullable && ! in_array($field->type, [FieldType::String, FieldType::Text, FieldType::Boolean, FieldType::Foreign], true)) {
                $shown = $field->type === FieldType::Enum ? "{$v}.{$field->name}_label && {$shown}" : "{$value} !== null && {$shown}";
            }

            $this->formatImport($imports, $field);
            $details[] = "                                            { label: t('{$slug}.{$field->name}'), value: {$shown} },";
        }

        $props = '';
        $destructure = '';
        $tabs = '';
        $hooks = '';
        $headerActions = '';
        $deleteDialog = '';

        if ($m->hasFiles()) {
            $imports['@admin-core/components/admin/AttachmentList'] = ['AttachmentSlots' => true];
            $props = '    attachments: Yuan.AdminCore.Data.AttachmentsData;';
            $destructure = 'attachments, ';
            $tabs = "                        {\n                            key: 'attachments',\n                            label: t('attachments.tab', { count: attachments.slots.reduce((sum, slot) => sum + slot.items.length, 0) }),\n                            content: (\n                                <section className=\"rounded bg-card px-6 py-4 text-card-foreground\">\n                                    <AttachmentSlots slots={attachments.slots} />\n                                </section>\n                            ),\n                        },";
        }

        if ($m->declares('edit')) {
            $imports["@/routes/{$slug}"]['edit'] = true;
            $imports['lucide-react'] = ['Pencil' => true];
            $imports['@admin-core/components/ui/button'] = ['Button' => true];
            $imports['@inertiajs/react']['Link'] = true;
            $headerActions .= "{{$this->can('edit')} && (<Button asChild><Link href={edit({$v}.id)}><Pencil />{t('table.edit')}</Link></Button>)}";
        }

        if ($m->declares('delete')) {
            $imports["@/routes/{$slug}"]['destroy'] = true;
            $imports['lucide-react'] = [...($imports['lucide-react'] ?? []), 'Trash2' => true];
            $imports['@admin-core/components/ui/button'] = ['Button' => true];
            $imports['@admin-core/components/admin/ConfirmDialog'] = ['ConfirmDialog' => true];
            $imports['@admin-core/hooks/useAction'] = ['useAction' => true];
            $imports['@inertiajs/react']['router'] = true;
            $hooks = "    const removing = useAction();\n    const [deleting, setDeleting] = useState(false);\n";
            $headerActions .= "{{$this->can('delete')} && (<Button variant=\"outline\" className=\"border-destructive bg-transparent text-destructive hover:bg-destructive/10 hover:text-destructive\" onClick={() => setDeleting(true)}><Trash2 />{t('table.delete')}</Button>)}";
            $deleteDialog = "            <ConfirmDialog\n                open={deleting}\n                onOpenChange={setDeleting}\n                title={t('{$slug}.delete_title')}\n                itemName={{$this->labelExpression($v)}}\n                loading={removing.pending}\n                onConfirm={() =>\n                    removing.run((done) =>\n                        router.delete(destroy({$v}.id), {\n                            onFinish: () => {\n                                done();\n                                setDeleting(false);\n                            },\n                        }),\n                    )\n                }\n            />";
        }

        return $this->render('module/js/Show', [
            'imports' => $this->tsImports($this->sortTsImports($imports)),
            'props' => $props,
            'destructure' => $destructure,
            'hooks' => $hooks,
            'title' => $this->labelExpression($v),
            'header_actions' => $this->headerActions($headerActions),
            'details' => implode("\n", $details),
            'tabs' => $tabs,
            'delete_dialog' => $deleteDialog,
        ]);
    }

    private function formPage(string $page): string
    {
        $m = $this->module;
        $v = $m->variable();
        $props = [];
        $destructure = [];
        $formProps = [];

        if ($m->hasOptions()) {
            $props[] = "    options: Modules.{$m->name}.Data.{$m->name}FormOptionsData;";
            $destructure[] = 'options';
            $formProps[] = 'options={options}';
        }

        if ($m->hasFiles()) {
            $props[] = '    attachments: Yuan.AdminCore.Data.AttachmentsData;';
            $destructure[] = 'attachments';
            $formProps[] = 'attachments={attachments}';
        }

        $initial = [];

        foreach ($m->columns() as $field) {
            $value = $page === 'Edit' ? "{$v}.{$field->name}" : match ($field->type) {
                FieldType::String => $field->nullable ? 'null' : "''",
                FieldType::Boolean => 'false',
                default => 'null',
            };
            $initial[] = "        {$field->name}: {$value},";
        }

        if ($m->hasFiles()) {
            $initial[] = '        files: {},';
        }

        // An empty destructuring pattern is a lint error: a page without props takes none.
        return $this->render("module/js/{$page}", [
            'props' => implode("\n", $props),
            'props_type' => $props === [] ? '' : "type Props = {\n".implode("\n", $props)."\n};\n\n",
            'signature' => $destructure === [] ? '' : '{ '.implode(', ', $destructure).' }: Props',
            'destructure' => implode(', ', $destructure),
            'initial' => implode("\n", $initial),
            'form_props' => implode(' ', $formProps),
            'title' => $this->labelExpression($v),
        ]);
    }

    private function form(): string
    {
        $m = $this->module;
        $slug = $m->slug;
        $input = $m->declares('create') ? "Store{$m->name}Data" : "Update{$m->name}Data";
        $imports = [
            '@admin-core/components/admin/FormErrorSummary' => ['FormErrorSummary' => true],
            '@admin-core/lib/i18n' => ['useT' => true],
            '@inertiajs/react' => ['type:InertiaFormProps' => true],
            'react' => ['type:ReactNode' => true],
        ];
        $props = [];
        $destructure = ['form', ...($m->fieldsOf(FieldType::Foreign) !== [] ? ['record'] : [])];
        $fields = [];

        if ($m->hasOptions()) {
            $props[] = "    options: Modules.{$m->name}.Data.{$m->name}FormOptionsData;";
            $destructure[] = 'options';
        }

        if ($m->hasFiles()) {
            $props[] = '    /** The attachment slots; on edit with their current files. */';
            $props[] = '    attachments: Yuan.AdminCore.Data.AttachmentsData;';
            $destructure[] = 'attachments';
        }

        foreach ($m->columns() as $field) {
            $n = $field->name;
            $common = "name=\"{$n}\" label={t('{$slug}.{$n}')}".($field->nullable || $field->type === FieldType::Boolean ? '' : ' required')." error={errors.{$n}}";
            $text = fn (string $component): string => "<{$component} {$common} value={data.{$n} ?? ''} onChange={(value) => form.setData('{$n}', ".($field->nullable ? "value === '' ? null : value" : 'value').')} />';
            $number = fn (string $component, string $extra = ''): string => "<{$component} {$common}{$extra} value={data.{$n}} onChange={(value) => form.setData('{$n}', value)} />";
            $control = match ($field->type) {
                FieldType::String => $text('TextField'),
                FieldType::Text => '<div className="md:col-span-2">'.$text('TextareaField').'</div>',
                FieldType::Integer => $number('NumberField', ' maximumFractionDigits={0}'),
                FieldType::Year => $number('NumberField', ' maximumFractionDigits={0} grouping={false}'),
                FieldType::Decimal => $number('NumberField', ' maximumFractionDigits={3}'),
                FieldType::Currency => $number('CurrencyField'),
                FieldType::Date => $number('DateField'),
                FieldType::DateTime => $number('DateField', ' withTime'),
                FieldType::Boolean => "<div className=\"flex items-end pb-2\"><SwitchField {$common} value={data.{$n} ?? false} onChange={(value) => form.setData('{$n}', value)} /></div>",
                FieldType::Enum => "<SelectField {$common} options={options.{$n}_options} placeholder=\"\" value={data.{$n} ?? ''} onChange={(value) => form.setData('{$n}', value === '' ? null : (value as Modules.{$m->name}.Enums.{$this->enumClass($field)}))} />",
                FieldType::Foreign => "<RelationSelect {$common} value={data.{$n}} valueLabel={record?.{$field->relationLabel()}} lookupRoute={{$field->relation()}Lookup} onChange={(value) => form.setData('{$n}', value)} />",
                FieldType::File => '',
            };
            $component = match ($field->type) {
                FieldType::String => ['TextField', 'TextField'],
                FieldType::Text => ['TextareaField', 'TextareaField'],
                FieldType::Integer, FieldType::Year, FieldType::Decimal => ['NumberField', 'NumberField'],
                FieldType::Currency => ['NumberField', 'CurrencyField'],
                FieldType::Date, FieldType::DateTime => ['DateField', 'DateField'],
                FieldType::Boolean => ['SwitchField', 'SwitchField'],
                FieldType::Enum => ['SelectField', 'SelectField'],
                FieldType::Foreign => ['RelationSelect', 'RelationSelect'],
                FieldType::File => ['', ''],
            };
            $imports["@admin-core/components/admin/{$component[0]}"][$component[1]] = true;

            if ($field->type === FieldType::Foreign) {
                $imports["@/routes/{$this->target($field)->slug}"]["lookup as {$field->relation()}Lookup"] = true;
            }

            $fields[] = '                '.$control;
        }

        $sections = '';

        if ($m->hasFiles()) {
            $imports['@admin-core/components/admin/FileField'] = ['AttachmentFields' => true];
            $sections = "            <Section title={t('{$slug}.section_files')}>\n                <AttachmentFields slots={attachments.slots} value={data.files ?? {}} errors={errors} onChange={(files) => form.setData('files', files)} />\n            </Section>";
        }

        return $this->render('module/js/Form', [
            'imports' => $this->tsImports($this->sortTsImports($imports)),
            'input' => $input,
            'props' => implode("\n", $props),
            'destructure' => implode(', ', $destructure),
            'fields' => implode("\n", $fields),
            'sections' => $sections,
        ]);
    }

    /**
     * @param  array<string, array<string, true>>  $imports
     */
    private function formatImport(array &$imports, FieldDefinition $field): void
    {
        $format = match ($field->type) {
            FieldType::Integer, FieldType::Decimal => 'formatNumber',
            FieldType::Currency => 'formatCurrency',
            FieldType::Date => 'formatLocalDate',
            FieldType::DateTime => 'formatLocalDateTime',
            default => null,
        };

        if ($format !== null) {
            $imports['@admin-core/lib/format'][$format] = true;
        }

        if ($field->type === FieldType::Enum) {
            $imports['@admin-core/components/admin/StatusBadge'] = ['StatusBadge' => true];
        }
    }

    /**
     * Core and package imports first, then the project's `@/` routes, like the hand-written modules.
     *
     * @param  array<string, array<string, true>>  $imports
     * @return array<string, array<string, true>>
     */
    private function sortTsImports(array $imports): array
    {
        uksort($imports, fn (string $a, string $b): int => [str_starts_with($a, '@/'), $a] <=> [str_starts_with($b, '@/'), $b]);

        return $imports;
    }

    // ---- Strings -----------------------------------------------------------

    private function strings(): string
    {
        $m = $this->module;
        $s = $m->slug;
        $strings = [
            'title' => $m->label,
            'description' => "Daftar {$this->lower()}.",
            'list' => "Daftar {$this->lower()}",
            'item' => $this->lower(),
            'search' => 'Cari…',
            'empty_title' => "Belum ada {$this->lower()}",
            'empty_text' => "Data {$this->lower()} yang dibuat akan muncul di sini.",
            'delete_title' => "Hapus {$this->lower()}",
            'create_title' => "Tambah {$this->lower()}",
            'edit_title' => 'Edit {name}',
            'section_main' => $m->label,
            'section_files' => 'Berkas',
            'yes' => 'Ya',
            'no' => 'Tidak',
        ];

        foreach ($m->columns() as $field) {
            $strings[$field->name] = $field->label();
        }

        return implode("\n", array_map(fn (string $key, string $text): string => "    '{$s}.{$key}': '".str_replace("'", "\\'", $text)."',", array_keys($strings), $strings));
    }
}
