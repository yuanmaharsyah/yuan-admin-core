<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Yuan\AdminCore\Data\AppData;
use Yuan\AdminCore\Data\AuthData;
use Yuan\AdminCore\Registry\MenuBuilder;

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
            'app' => fn (): AppData => new AppData(
                name: config()->string('app.name'),
                locale: app()->getLocale(),
                timezone: config()->string('app.timezone'),
                panel_open: $request->cookie('admin_panel') !== 'false',
            ),
        ];
    }
}
