<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Http;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Yuan\AdminCore\Data\ErrorPageData;
use Yuan\AdminCore\Http\Middleware\HandleInertiaRequests;

/**
 * Renders 403 / 404 / 419 / 500 / 503 as the `Core::Errors/Show` page
 * (frontend-behavior §2). A 500 keeps Laravel's debug page while APP_DEBUG is
 * on, and JSON requests keep JSON. Projects call it once from bootstrap/app.php:
 * `->withExceptions(fn (Exceptions $e) => ErrorPages::register($e))`.
 */
final class ErrorPages
{
    public const array STATUSES = [403, 404, 419, 500, 503];

    public static function register(Exceptions $exceptions): void
    {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();

            if (! in_array($status, self::STATUSES, true) || $request->expectsJson() || ($status === 500 && config()->boolean('app.debug'))) {
                return $response;
            }

            // A 404 on an unknown route or a 419 from CSRF never reached the
            // shared-props middleware; share the core props for the layout.
            if (Inertia::getShared('app') === null) {
                Inertia::share(app(HandleInertiaRequests::class)->share($request));
            }

            $signedIn = $request->user() !== null;
            $home = $signedIn ? route(config()->string('admin-core.routes.home')) : route('login');

            return Inertia::render('Core::Errors/Show', new ErrorPageData(status: $status, home_href: $home, signed_in: $signedIn))
                ->toResponse($request)
                ->setStatusCode($status);
        });
    }
}
