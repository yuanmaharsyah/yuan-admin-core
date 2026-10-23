<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After an admin created the account or reset its password, the user can
 * only change it or sign out (the reference's mandatory state). Everything
 * else redirects to the change-password page; JSON gets a 403. In the
 * skeleton's `web` group, after `active`.
 */
final class RequirePasswordChange
{
    public const array ALLOWED = ['account.password.edit', 'account.password.update', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->getAttribute('must_change_password') !== true || in_array($request->route()?->getName(), self::ALLOWED, true)) {
            return $next($request);
        }

        abort_if($request->expectsJson(), 403, __('Ganti kata sandi Anda terlebih dahulu.'));

        return redirect()->route('account.password.edit');
    }
}
