<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Levels\Data;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class StoreLevelData extends Data
{
    public function __construct(
        public string $label,
        public ?string $description,
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:60', Rule::unique('roles', 'label')],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return ['label' => __('nama'), 'description' => __('deskripsi')];
    }
}
