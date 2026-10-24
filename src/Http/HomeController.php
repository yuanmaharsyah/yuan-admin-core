<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Yuan\AdminCore\Data\NoAccessPageData;
use Yuan\AdminCore\Registry\MenuBuilder;

/**
 * `/home` (`admin-core.home`), where sign-in and "back home" links land: the
 * first menu item the user may open (Beranda, with `dashboard.view`, comes
 * first), else the no-access page.
 */
final class HomeController
{
    public function __invoke(Request $request, MenuBuilder $menu): RedirectResponse|Response
    {
        /** @var Authenticatable $user */
        $user = $request->user();
        $first = $menu->build($user)[0]->items[0] ?? null;

        return $first !== null
            ? redirect()->to($first->href)
            : Inertia::render('Core::Errors/NoAccess', new NoAccessPageData(logout_href: route('logout')));
    }

    /** The URL of the landing redirect. */
    public static function url(): string
    {
        return route('admin-core.home');
    }
}
