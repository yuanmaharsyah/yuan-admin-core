<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Settings\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Data\OptionData;
use Yuan\AdminCore\Modules\Settings\Data\SettingFieldData;
use Yuan\AdminCore\Modules\Settings\Data\SettingsPageData;
use Yuan\AdminCore\Modules\Settings\Data\SettingsTabData;
use Yuan\AdminCore\Modules\Settings\Data\UpdateSettingsData;
use Yuan\AdminCore\Settings\BrandingStorage;
use Yuan\AdminCore\Settings\SettingDefinition;
use Yuan\AdminCore\Settings\Settings;
use Yuan\AdminCore\Settings\SettingType;

/**
 * Pengaturan (spec §7.3, ui-design §4.4), Super Admin only: one tab per
 * registry group, each saved on its own with a stale-update check.
 */
final class SettingsController
{
    public function __construct(private readonly Settings $settings) {}

    public function index(Request $request): Response
    {
        Gate::authorize('admin-core.superadmin');

        $groups = $this->settings->groups();
        $tab = $request->query('tab');

        return Inertia::render('Settings::Index', new SettingsPageData(
            tabs: array_map(fn (string $key, string $label): SettingsTabData => new SettingsTabData(
                key: $key,
                label: __($label),
                lock_version: $this->settings->version($key),
                fields: array_values(array_map($this->field(...), $this->settings->group($key))),
            ), array_keys($groups), $groups),
            tab: is_string($tab) && isset($groups[$tab]) ? $tab : (string) array_key_first($groups),
        ));
    }

    public function update(string $group, UpdateSettingsData $data): RedirectResponse
    {
        Gate::authorize('admin-core.superadmin');
        abort_if($this->settings->group($group) === [], 404);

        $changed = $this->settings->save($group, $data->values, $data->lock_version);
        Inertia::flash((new FlashData(success: $changed === [] ? __('Tidak ada perubahan.') : __('Pengaturan disimpan.')))->toArray());

        return to_route('settings.index', ['tab' => $group]);
    }

    private function field(SettingDefinition $definition): SettingFieldData
    {
        $image = BrandingStorage::FIELDS[$definition->key] ?? null;
        $value = $definition->type->display($this->settings->get($definition->key));

        return new SettingFieldData(
            name: $definition->name,
            type: $definition->type->value,
            label: __($definition->label),
            help: self::translate($definition->help),
            value: is_float($value) ? (int) $value : (is_scalar($value) ? $value : null),
            options: $definition->type === SettingType::Enum
                ? array_map(fn (string $value, string $label): OptionData => new OptionData(value: $value, label: __($label)), array_keys($definition->options), $definition->options)
                : [],
            accept: $image['mimes'] ?? [],
            max_kb: $image['max_kb'] ?? null,
        );
    }

    private static function translate(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return __($text);
    }
}
