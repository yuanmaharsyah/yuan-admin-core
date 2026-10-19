<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

uses(RefreshDatabase::class);

it('gives every event an Indonesian label and a badge', function (AuditEvent $event): void {
    expect($event->label())->not->toBe('')
        ->and($event->badge())->toBeIn(['success', 'info', 'destructive', 'warning', 'neutral']);
})->with(AuditEvent::cases());

it('uses a fixed log name for sign-in and retention events only', function (): void {
    expect(AuditEvent::Login->logName())->toBe('auth')
        ->and(AuditEvent::LoginFailed->logName())->toBe('auth')
        ->and(AuditEvent::AuditPruned->logName())->toBe('system')
        ->and(AuditEvent::Created->logName())->toBeNull();
});

it('accepts a taxonomy event given as a string', function (): void {
    Audit::record('settings_updated', meta: ['keys' => ['app.name']]);

    expect(latestActivity()->event)->toBe('settings_updated')
        ->and(latestActivity()->log_name)->toBe('system');
});

it('rejects an unknown event name', function (): void {
    expect(fn () => Audit::record('approved'))
        ->toThrow(InvalidArgumentException::class, 'Unknown audit event [approved]');
});

it('accepts a custom action declared by the module, named or taken from the subject', function (): void {
    Audit::record('approve', module: 'demo');
    Audit::record('approve', DemoItem::query()->create(['name' => 'A']));

    expect(latestActivity()->only(['event', 'log_name']))->toBe(['event' => 'approve', 'log_name' => 'demo']);
});

it('rejects a custom action the module does not declare, and standard actions', function (string $event, ?string $module): void {
    expect(fn () => Audit::record($event, module: $module))->toThrow(InvalidArgumentException::class);
})->with([
    'undeclared' => ['approve', 'notes'],
    'no module' => ['approve', null],
    'standard action' => ['create', 'demo'],
]);
