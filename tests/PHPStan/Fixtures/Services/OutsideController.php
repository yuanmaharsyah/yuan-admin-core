<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\PHPStan\Fixtures\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Yuan\AdminCore\Permissions\Level;

/**
 * Outside a controller namespace: neither rule applies.
 */
final class OutsideController
{
    public function render(Level $level, Request $request): Response
    {
        return Inertia::render('Levels::Show', ['level' => $level, 'all' => $request->all()]);
    }
}
