<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One attached file. `url_href` answers a fresh signed download URL (they
 * expire after a few minutes, so the page asks on click); `destroy_href` is
 * null when the user may not delete it.
 */
#[TypeScript]
final class AttachmentData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $mime,
        public int $size,
        public ?string $uploaded_by,
        public string $created_at,
        public string $url_href,
        public ?string $destroy_href,
    ) {}
}
