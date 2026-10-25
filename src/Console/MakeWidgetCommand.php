<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Yuan\AdminCore\Dashboard\WidgetSize;
use Yuan\AdminCore\Dashboard\WidgetType;
use Yuan\AdminCore\Generator\ModuleBlueprint;
use Yuan\AdminCore\Generator\StubRenderer;

/**
 * make:widget (DEVELOPER R16): a Beranda widget of a project module with
 * working code on the module's model (through `visibleTo()`), its test, and
 * its line in `module.php` → `widgets`.
 */
final class MakeWidgetCommand extends Command
{
    protected $signature = 'make:widget
        {module : the module name (Invoice)}
        {name : the widget class (UnpaidTotal)}
        {--type=stat : stat, stat_stack, segment, bar_chart or table}
        {--size=sm : sm, md, lg or full}
        {--path= : the module root to find the module in (default: the project module root)}';

    protected $description = 'Generate a Beranda widget for a module (admin-dashboard-widget skill)';

    public function handle(Filesystem $files): int
    {
        $module = $this->text('module');
        $class = $this->text('name');
        $type = WidgetType::tryFrom($this->stringOption('type') ?? '');
        $size = WidgetSize::tryFrom($this->stringOption('size') ?? '');
        $folder = rtrim($this->stringOption('path') ?? ModuleBlueprint::projectRoot(), '/')."/{$module}";
        $config = is_file("{$folder}/module.php") ? require "{$folder}/module.php" : null;

        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $class) !== 1) {
            return $this->refuse("Widget name [{$class}]: use PascalCase, e.g. UnpaidTotal.");
        }

        if ($type === null) {
            return $this->refuse('Type must be one of: '.implode(', ', array_column(WidgetType::cases(), 'value')).'.');
        }

        if ($size === null) {
            return $this->refuse('Size must be one of: '.implode(', ', array_column(WidgetSize::cases(), 'value')).'.');
        }

        if (! is_array($config)) {
            return $this->refuse("No module {$module} in {$folder}.");
        }

        if ((bool) ($config['superadmin_only'] ?? false)) {
            return $this->refuse("{$module} is superadmin_only: it has no permission a widget could be shown by.");
        }

        if ($files->exists("{$folder}/Widgets/{$class}.php")) {
            return $this->refuse("{$folder}/Widgets/{$class}.php already exists.");
        }

        $slug = (string) $config['slug'];
        $label = Str::ucfirst(Str::lower(Str::headline($class)));
        $values = [
            'Module' => $module,
            'Model' => $module,
            'Class' => $class,
            'key' => $slug.'.'.Str::snake($class),
            'label' => $label,
            'Type' => Str::studly($type->value),
            'Size' => Str::studly($size->value),
            'icon' => (string) $config['icon'],
            'slug' => $slug,
            'permission' => "{$slug}.view",
            'unit' => Str::lower((string) $config['label']),
        ];
        $imports = [
            'Illuminate\Contracts\Auth\Authenticatable',
            "Modules\\{$module}\\Models\\{$module}",
            'Yuan\AdminCore\Dashboard\DashboardRange',
            'Yuan\AdminCore\Dashboard\Widget',
            'Yuan\AdminCore\Dashboard\WidgetSize',
            'Yuan\AdminCore\Dashboard\WidgetType',
            'Yuan\AdminCore\Data\Widgets\\'.class_basename($type->dataClass()),
            ...($type === WidgetType::StatStack || $type === WidgetType::Segment || $type === WidgetType::BarChart ? ['Yuan\AdminCore\Data\Widgets\WidgetFigureData'] : []),
            ...($type === WidgetType::Table ? ['Yuan\AdminCore\Data\OptionData'] : []),
        ];

        if ($type === WidgetType::Segment) {
            $enum = $this->firstEnum("Modules\\{$module}\\Models\\{$module}");

            if ($enum === null) {
                return $this->refuse("A segment widget counts by an enum column; {$module} has none.");
            }

            [$column, $enumClass] = $enum;
            $values = [...$values, 'column' => $column, 'Enum' => class_basename($enumClass)];
            $imports[] = $enumClass;
        }

        sort($imports);
        $values['imports'] = implode("\n", array_map(fn (string $import): string => "use {$import};", $imports));
        $values['doc'] = match ($type) {
            WidgetType::Stat => "{$label}: {$values['unit']} created in the range",
            WidgetType::StatStack => "{$label}: all {$values['unit']}, those created in the range and today",
            WidgetType::Segment => "{$label}: {$values['unit']} created in the range, per ".($values['column'] ?? ''),
            WidgetType::BarChart => "{$label}: {$values['unit']} created per day of the range",
            WidgetType::Table => "{$label}: the latest {$values['unit']}",
        };
        $values['body'] = rtrim(StubRenderer::render("widget/{$type->value}", $values));

        $files->ensureDirectoryExists("{$folder}/Widgets");
        $files->ensureDirectoryExists("{$folder}/Tests/Feature");
        $files->put("{$folder}/Widgets/{$class}.php", StubRenderer::render('widget/widget', $values));
        $files->put("{$folder}/Tests/Feature/{$class}WidgetTest.php", StubRenderer::render('widget/test', $values));
        $this->register($files, "{$folder}/module.php", "Modules\\{$module}\\Widgets\\{$class}");

        $base = $this->laravel->basePath();

        if (is_file("{$base}/vendor/bin/pint")) {
            Process::path($base)->run(['vendor/bin/pint', "{$folder}/Widgets/{$class}.php", "{$folder}/Tests/Feature/{$class}WidgetTest.php", "{$folder}/module.php"]);
        }

        $this->components->info("Widget {$values['key']} written to {$folder}/Widgets/{$class}.php and listed in module.php.");

        return self::SUCCESS;
    }

    /** Adds the class to `'widgets' => [...]`. */
    private function register(Filesystem $files, string $file, string $class): void
    {
        $source = $files->get($file);
        $updated = (string) preg_replace_callback(
            "/'widgets' => \[(.*?)\],/s",
            function (array $match) use ($class): string {
                $listed = array_values(array_filter(array_map(trim(...), explode(',', $match[1])), fn (string $item): bool => $item !== ''));
                $listed[] = "{$class}::class";

                return "'widgets' => [\n        ".implode(",\n        ", $listed).",\n    ],";
            },
            $source,
            1,
        );
        $files->put($file, $updated);
    }

    /**
     * The first enum-cast column of the model: [column, enum class].
     *
     * @return array{string, class-string}|null
     */
    private function firstEnum(string $model): ?array
    {
        if (! class_exists($model) || ! is_subclass_of($model, Model::class)) {
            return null;
        }

        foreach ((new $model)->getCasts() as $column => $cast) {
            if (enum_exists($cast) && is_subclass_of($cast, BackedEnum::class)) {
                return [$column, $cast];
            }
        }

        return null;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function refuse(string $message): int
    {
        $this->components->error($message);

        return self::FAILURE;
    }

    private function text(string $argument): string
    {
        $value = $this->argument($argument);

        return is_string($value) ? $value : '';
    }
}
