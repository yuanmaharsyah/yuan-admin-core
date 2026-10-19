<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Yuan\AdminCore\Data\FlashData;

/**
 * Alias `active`; projects also append it to the `web` group so every route
 * is covered. Logs out a deactivated user, and a session whose stored version
 * isn't the user's `session_version` (revoked by deactivation, a level change
 * or a password change). Works on every session driver: the version lives in
 * the session itself. Guests and the `logout` route pass through.
 */
final class EnsureUserIsActive
{
    public const string SESSION_VERSION = 'admin_core.session_version';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Model && ! $request->routeIs('logout') && ! $this->isValid($user, $request)) {
            Auth::guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Inertia::flash((new FlashData(error: __('Sesi Anda telah berakhir. Silakan masuk kembali.')))->toArray());

            throw new AuthenticationException;
        }

        return $next($request);
    }

    /**
     * A session without a stored version (signed in without a Login event,
     * e.g. `actingAs()`) counts as version 0, so it is valid only until the
     * user's first revocation.
     */
    private function isValid(Model $user, Request $request): bool
    {
        return (bool) $user->getAttribute('is_active')
            && $request->session()->get(self::SESSION_VERSION, 0) === (int) $user->getAttribute('session_version');
    }
}
