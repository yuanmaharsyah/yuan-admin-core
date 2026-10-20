<?php

declare(strict_types=1);

use Yuan\AdminCore\Attachments\AttachmentController;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

it('refuses a model without the traits or a module that cannot edit', function (): void {
    expect(fn () => AttachmentController::routes(ImmutableActivity::class))->toThrow(InvalidArgumentException::class, 'need HasAttachments')
        ->and(fn () => AttachmentController::routes(DemoItem::class))->toThrow(InvalidArgumentException::class, 'declares view and edit');
});
