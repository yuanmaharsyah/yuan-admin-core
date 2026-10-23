<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Exports\ExportStatus;

/**
 * One of the user's queued downloads. `url_href` answers a fresh signed file
 * URL (asked for on click); it is null unless the file is ready and unexpired.
 */
#[TypeScript]
final class DownloadItemData extends Data
{
    public function __construct(
        public int $id,
        public string $module_label,
        public ?string $file_name,
        public int $row_count,
        public ExportStatus $status,
        public ?string $error,
        public string $created_at,
        public ?string $expires_at,
        public ?string $url_href,
    ) {}
}
