<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Yuan\AdminCore\Data\FlashData;

/**
 * Alias `active`. Logs out a user who was deactivated, or whose sessions were
 * revoked (`sessions_invalidated_at`) after this session logged in. Works on
 * every session driver: the login time lives in the session itself.
 */
final class EnsureUserIsActive
{
    public const string LOGIN_TIME = 'admin_core.login_time';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Model && ! $this->isValid($user, $request)) {
            Auth::guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Inertia::flash((new FlashData(error: __('Sesi Anda telah berakhir. Silakan masuk kembali.')))->toArray());

            throw new AuthenticationException;
        }

        return $next($request);
    }

    private function isValid(Model $user, Request $request): bool
    {
        $revokedAt = $user->getAttribute('sessions_invalidated_at');
        $loggedInAt = $request->session()->get(self::LOGIN_TIME, 0);

        return (bool) $user->getAttribute('is_active')
            && (! $revokedAt instanceof Carbon || $revokedAt->getTimestamp() <= (is_int($loggedInAt) ? $loggedInAt : 0));
    }
}
