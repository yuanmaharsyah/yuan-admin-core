<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Tests\Fixtures\Models\User;

#[Tries(1)]
#[Backoff(1)]
#[Timeout(30)]
final class AuditedJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        Audit::actingAs($this->user, fn () => Audit::record(AuditEvent::Exported, meta: ['rows' => 3], module: 'demo'));
    }
}
