<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;
use Yuan\AdminCore\Data\AppData;
use Yuan\AdminCore\Data\AuthData;
use Yuan\AdminCore\Data\UserData;
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
            'auth' => fn (): AuthData => new AuthData(
                user: $request->user() === null ? null : UserData::fromUser($request->user()),
                is_super_admin: $request->user() !== null && Gate::forUser($request->user())->allows('admin-core.superadmin'),
            ),
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
