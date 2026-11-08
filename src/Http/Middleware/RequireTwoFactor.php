<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengaturan → Keamanan → wajib_2fa: a signed-in user without confirmed
 * two-step verification only reaches Akun (to set it up), Fortify's two-step
 * and password-confirm routes, and logout. Everything else redirects to
 * Akun → Keamanan; JSON gets a 403. Does nothing while the project hasn't
 * turned on Fortify's two-step. In the skeleton's `web` group, after
 * RequirePasswordChange.
 */
final class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = (string) $request->route()?->getName();

        if ($user === null
            || settings('keamanan.wajib_2fa') !== true
            || ! Route::has('two-factor.enable')
            || $user->getAttribute('two_factor_confirmed_at') !== null
            || $route === 'logout'
            || Str::startsWith($route, ['account.', 'two-factor.', 'password.confirm'])) {
            return $next($request);
        }

        abort_if($request->expectsJson(), 403, __('Aktifkan verifikasi dua langkah terlebih dahulu.'));

        return redirect()->route('account.profile.edit', ['tab' => 'keamanan'])
            ->with('status', __('Aktifkan verifikasi dua langkah untuk melanjutkan.'));
    }
}
