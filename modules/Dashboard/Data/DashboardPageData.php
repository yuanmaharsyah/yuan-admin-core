<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Dashboard\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Data\Widgets\WidgetMetaData;

/**
 * `Dashboard::Index`'s `dashboard` prop (frontend-behavior §8.6): the
 * permitted widgets, without their data; each widget's data is the deferred
 * page prop `widgets[].prop`.
 */
#[TypeScript]
final class DashboardPageData extends Data
{
    /**
     * @param  list<WidgetMetaData>  $widgets
     */
    public function __construct(
        public array $widgets,
        #[LiteralTypeScriptType("'7d' | '30d' | '90d'")]
        public string $range,
    ) {}
}
