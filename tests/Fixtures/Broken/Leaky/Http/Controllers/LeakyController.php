<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Models\Leaky;

final class LeakyController
{
    public function index(): RedirectResponse
    {
        // "publish" is not a declared action of this module.
        Audit::record('publish', Leaky::query()->firstOrFail());

        return back();
    }
}
