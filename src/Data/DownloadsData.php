<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Exports\ExportStatus;
use Yuan\AdminCore\Exports\QueuedExport;

/** The shared `downloads` prop: the topbar badge counts the user's finished, unexpired downloads. */
#[TypeScript]
final class DownloadsData extends Data
{
    public function __construct(
        public int $ready_count,
    ) {}

    public static function forUser(?Authenticatable $user): self
    {
        return new self(ready_count: $user === null ? 0 : QueuedExport::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('status', ExportStatus::Ready)
            ->where('expires_at', '>', now())
            ->count());
    }
}
