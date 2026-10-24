<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Settings\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Data\OptionData;

/** One field of a Settings tab, rendered by its type; `value` is the form's view of it (an image's URL). */
#[TypeScript]
final class SettingFieldData extends Data
{
    /**
     * @param  list<OptionData>  $options
     * @param  list<string>  $accept  An image's file extensions.
     */
    public function __construct(
        public string $name,
        #[LiteralTypeScriptType("'string' | 'text' | 'integer' | 'boolean' | 'enum' | 'phone' | 'whatsapp' | 'postal_code' | 'social' | 'image'")]
        public string $type,
        public string $label,
        public ?string $help,
        #[LiteralTypeScriptType('string | number | boolean | null')]
        public string|int|bool|null $value,
        public array $options = [],
        public array $accept = [],
        public ?int $max_kb = null,
    ) {}
}
