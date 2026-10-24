<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yuan\AdminCore\Attachments\AttachmentController;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

it('refuses a model without the traits', function (): void {
    expect(fn () => AttachmentController::routes(ImmutableActivity::class))->toThrow(InvalidArgumentException::class, 'need HasAttachments');
});

it('adds the delete route only for a module that declares edit', function (): void {
    Route::prefix('admin/demo')->name('demo.')->group(fn () => AttachmentController::routes(DemoItem::class));
    Route::getRoutes()->refreshNameLookups();

    expect(Route::has('demo.attachments.url'))->toBeTrue()
        ->and(Route::has('demo.attachments.download'))->toBeTrue()
        ->and(Route::has('demo.attachments.destroy'))->toBeFalse();
});
