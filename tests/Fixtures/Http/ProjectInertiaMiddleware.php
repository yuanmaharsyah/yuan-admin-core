<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Http;

use Illuminate\Http\Request;
use Yuan\AdminCore\Data\AppData;
use Yuan\AdminCore\Http\Middleware\HandleInertiaRequests;

/** A project's subclass adding one prop, as the skeleton's middleware does. */
final class ProjectInertiaMiddleware extends HandleInertiaRequests
{
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'project' => fn (): AppData => new AppData(name: 'Project', locale: 'id', timezone: 'UTC', panel_open: true, maintenance_banner: null),
        ];
    }
}
