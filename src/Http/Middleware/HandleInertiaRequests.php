<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Yuan\AdminCore\Data\AppData;
use Yuan\AdminCore\Data\AuthData;
use Yuan\AdminCore\Data\DownloadsData;
use Yuan\AdminCore\Data\SiteAccessPageData;
use Yuan\AdminCore\Registry\MenuBuilder;
use Yuan\AdminCore\Settings\Settings;

/**
 * Shared props, all Data objects. Projects add their own by extending this
 * class and merging parent::share(). Flash messages use Inertia's native
 * flash (FlashData), not a shared prop.
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => fn (): AuthData => AuthData::fromUser($request->user()),
            'menu' => fn (): array => $request->user() === null ? [] : app(MenuBuilder::class)->build($request->user()),
            'app' => fn (): AppData => self::app($request),
            'downloads' => fn (): DownloadsData => DownloadsData::forUser($request->user()),
        ];
    }

    private static function app(Request $request): AppData
    {
        /** @var array<string, string|int|bool|null> $settings */
        $settings = app(Settings::class)->publicValues();
        $mode = settings('akses.mode');
        $fallback = config('admin-core.branding.logo');

        return new AppData(
            name: config()->string('app.name'),
            locale: app()->getLocale(),
            timezone: config()->string('app.timezone'),
            panel_open: $request->cookie('admin_panel') !== 'false',
            site_access: in_array($mode, ['maintenance', 'coming_soon'], true)
                ? new SiteAccessPageData(mode: $mode, message: is_string($message = settings('akses.pesan')) ? $message : null)
                : null,
            route: $request->route()?->getName(),
            logo: is_string($settings['umum.logo_persegi'] ?? null) ? $settings['umum.logo_persegi'] : (is_string($fallback) && $fallback !== '' ? asset($fallback) : null),
            logo_landscape: is_string($settings['umum.logo_lanskap'] ?? null) ? $settings['umum.logo_lanskap'] : null,
            favicon: is_string($settings['umum.favicon'] ?? null) ? $settings['umum.favicon'] : null,
            settings: $settings,
        );
    }
}
