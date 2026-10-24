<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Settings;

use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Concurrency\StaleUpdateException;

/**
 * The settings registry (spec §7.3): keys declared in `admin-core.settings`
 * (group = Settings tab), stored values in `settings`, cached until a save.
 * Read with `settings('kontak.telepon')`; a key with no stored value gives its
 * declared default.
 */
final class Settings
{
    public const string CACHE_KEY = 'admin-core:settings';

    /** Setting => the config key it overrides once stored. */
    public const array CONFIG = [
        'umum.nama_aplikasi' => 'app.name',
        'keamanan.session_lifetime' => 'session.lifetime',
        'ekspor.queue_threshold' => 'admin-core.exports.queue_threshold',
    ];

    /** @var array<string, SettingDefinition>|null */
    private ?array $definitions = null;

    /**
     * @return array<string, SettingDefinition>
     */
    public function definitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $definitions = [];

        foreach (config()->array('admin-core.settings') as $group => $tab) {
            foreach (is_array($tab['fields'] ?? null) ? $tab['fields'] : [] as $name => $field) {
                $definition = SettingDefinition::fromConfig((string) $group, (string) $name, is_array($field) ? $field : []);
                $definitions[$definition->key] = $definition;
            }
        }

        return $this->definitions = $definitions;
    }

    /**
     * Tab key => label, in config order.
     *
     * @return array<string, string>
     */
    public function groups(): array
    {
        return array_map(fn (mixed $tab): string => is_array($tab) && is_string($tab['label'] ?? null) ? $tab['label'] : '', config()->array('admin-core.settings'));
    }

    /**
     * @return array<string, SettingDefinition>
     */
    public function group(string $group): array
    {
        return array_filter($this->definitions(), fn (SettingDefinition $definition): bool => $definition->group === $group);
    }

    public function definition(string $key): SettingDefinition
    {
        return $this->definitions()[$key] ?? throw new InvalidArgumentException("Unknown setting [{$key}].");
    }

    public function get(string $key): mixed
    {
        $definition = $this->definition($key);

        return $this->stored()[$key] ?? $definition->default;
    }

    /**
     * Every public setting as pages get it (`SettingType::publicValue()`).
     *
     * @return array<string, mixed>
     */
    public function publicValues(): array
    {
        $values = [];

        foreach ($this->definitions() as $key => $definition) {
            if ($definition->public) {
                $values[$key] = $definition->type->publicValue($this->get($key));
            }
        }

        return $values;
    }

    /** The tab's version: the sum of its rows' `lock_version`, which rises on every change. */
    public function version(string $group): int
    {
        return (int) DB::table('settings')->whereIn('key', array_keys($this->group($group)))->sum('lock_version');
    }

    /**
     * Saves a tab's validated input: one `settings_updated` row with the old
     * and new value of each changed key. An image left empty keeps its file.
     * `$version` is the version the form loaded (null: no stale check, the CLI).
     *
     * @param  array<string, mixed>  $input  Field name => value.
     * @return list<string> The changed keys.
     */
    public function save(string $group, array $input, ?int $version = null): array
    {
        $definitions = $this->group($group);
        $replaced = [];

        $changed = DB::transaction(function () use ($group, $definitions, $input, $version, &$replaced): array {
            $rows = DB::table('settings')->whereIn('key', array_keys($definitions))->lockForUpdate()->get()->keyBy('key');

            if ($version !== null && (int) $rows->sum('lock_version') !== $version) {
                throw new StaleUpdateException;
            }

            $old = [];
            $new = [];

            foreach ($definitions as $key => $definition) {
                if (! array_key_exists($definition->name, $input)) {
                    continue;
                }

                $value = $input[$definition->name];
                $current = isset($rows[$key]) ? json_decode((string) $rows[$key]->value, true) : null;

                if ($definition->type === SettingType::Image) {
                    if (! $value instanceof UploadedFile) {
                        continue;
                    }

                    $value = BrandingStorage::store($value);
                    $replaced[] = is_string($current) ? $current : null;
                } else {
                    $value = $definition->type->normalize($value, $definition);
                }

                if ($value === $current) {
                    continue;
                }

                DB::table('settings')->updateOrInsert(['key' => $key], [
                    'value' => json_encode($value),
                    'lock_version' => (isset($rows[$key]) ? (int) $rows[$key]->lock_version : 0) + 1,
                    'updated_at' => now(),
                    'created_at' => isset($rows[$key]) ? $rows[$key]->created_at : now(),
                ]);
                $old[$definition->name] = $current;
                $new[$definition->name] = $value;
            }

            if ($new !== []) {
                // Nested by group so masked_fields patterns match the field name.
                Audit::record(AuditEvent::SettingsUpdated, meta: ['old' => [$group => $old], 'new' => [$group => $new]]);
            }

            return array_map(fn (string $name): string => "{$group}.{$name}", array_keys($new));
        });

        array_map(BrandingStorage::delete(...), $replaced);
        $this->forget();

        return $changed;
    }

    /**
     * Stored values replace the config they override (`CONFIG`), so every
     * reader (StartSession, ExportManager, mail, the page title) follows
     * them. Runs once per request, when the app has booted.
     */
    public function applyToConfig(): void
    {
        foreach (self::CONFIG as $setting => $key) {
            if (($value = $this->get($setting)) !== null) {
                config([$key => $value]);
            }
        }
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Stored values by key. Before the tables exist (a fresh install, or
     * after `migrate:rollback`: the settings table, or a database cache
     * store's table) there are none and nothing is cached, so artisan can
     * still boot and migrate.
     *
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        try {
            $cached = Cache::get(self::CACHE_KEY);

            if (is_array($cached)) {
                return $cached;
            }

            $values = DB::table('settings')->pluck('value', 'key')->map(fn (mixed $value): mixed => json_decode((string) $value, true))->all();
            Cache::forever(self::CACHE_KEY, $values);

            return $values;
        } catch (QueryException) {
            return [];
        }
    }
}
