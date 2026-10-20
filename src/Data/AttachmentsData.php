<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The Lampiran tab of a Show page: the files, and where and what may be uploaded (null: no upload). */
#[TypeScript]
final class AttachmentsData extends Data
{
    /**
     * @param  list<AttachmentData>  $items
     * @param  list<string>  $accept  Allowed extensions, without the dot.
     */
    public function __construct(
        public array $items,
        public ?string $upload_href,
        public array $accept,
        public int $max_kb,
    ) {}
}
