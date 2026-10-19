<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The `breadcrumbs` page prop (frontend-behavior §3). A wrapper, because
 * Inertia props are Data objects, never bare lists.
 */
#[TypeScript]
final class BreadcrumbsData extends Data
{
    /**
     * @param  list<BreadcrumbData>  $items
     */
    public function __construct(
        public array $items,
    ) {}

    public static function of(BreadcrumbData ...$items): self
    {
        return new self(array_values($items));
    }
}
