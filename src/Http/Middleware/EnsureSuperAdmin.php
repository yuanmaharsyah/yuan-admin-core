<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias `superadmin`: Levels, the matrix, Settings, Trash, Health and every
 * superadmin_only module (spec §6.3).
 */
final class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() !== null && Gate::forUser($request->user())->allows('admin-core.superadmin'), 403);

        return $next($request);
    }
}
