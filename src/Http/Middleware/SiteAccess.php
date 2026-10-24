<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Yuan\AdminCore\Data\SiteAccessPageData;

/**
 * Settings → Akses Situs (spec §7.3) on public routes: `maintenance` answers
 * 503 with Retry-After, `coming_soon` 200 with noindex. The admin area, the
 * account pages, the auth routes and a signed-in Super Admin always pass.
 * Projects append it to the `web` group after HandleInertiaRequests.
 */
final class SiteAccess
{
    public const int RETRY_AFTER = 3600;

    public function handle(Request $request, Closure $next): Response
    {
        $mode = settings('akses.mode');

        if (! in_array($mode, ['maintenance', 'coming_soon'], true) || self::exempt($request)) {
            return $next($request);
        }

        $message = settings('akses.pesan');
        $response = Inertia::render('Core::Errors/SiteAccess', new SiteAccessPageData(mode: $mode, message: is_string($message) ? $message : null))->toResponse($request);

        if ($mode === 'maintenance') {
            $response->setStatusCode(503);
            $response->headers->set('Retry-After', (string) self::RETRY_AFTER);
        } else {
            $response->headers->set('X-Robots-Tag', 'noindex');
        }

        return $response;
    }

    private static function exempt(Request $request): bool
    {
        $prefix = trim(config()->string('admin-core.routes.prefix'), '/');
        $user = $request->user();

        return $request->is($prefix, "{$prefix}/*", 'account', 'account/*')
            || $request->routeIs('login', 'login.*', 'logout', 'password.*', 'user-password.*', 'two-factor.*', 'verification.*')
            || (is_object($user) && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
    }
}
