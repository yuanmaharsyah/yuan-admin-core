<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Http\Middleware\HandleInertiaRequests;

use function Pest\Laravel\get;

beforeEach(function (): void {
    Route::middleware(['web', HandleInertiaRequests::class])->group(function (): void {
        Route::get('/probe', fn () => Inertia::render('Demo::Index'));
        Route::get('/save', function () {
            Inertia::flash((new FlashData(success: 'Tersimpan.'))->toArray());

            return redirect('/probe');
        });
    });
});

it('delivers flash once, with only the keys that were set', function (): void {
    get('/save')->assertRedirect('/probe');

    AssertableInertia::fromTestResponse(get('/probe'))
        ->hasFlash('success', 'Tersimpan.')
        ->missingFlash('error')
        ->missingFlash('info');

    AssertableInertia::fromTestResponse(get('/probe'))->missingFlash('success');
});
