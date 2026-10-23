<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Levels\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The whole matrix of a level in one PUT: module slug => granted actions (frontend-behavior §8.2). */
#[TypeScript]
final class SaveMatrixData extends Data
{
    /**
     * @param  array<string, list<string>>  $grants
     */
    public function __construct(
        public array $grants = [],
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'grants' => ['present', 'array'],
            'grants.*' => ['array'],
            'grants.*.*' => ['string', 'max:40'],
        ];
    }
}
