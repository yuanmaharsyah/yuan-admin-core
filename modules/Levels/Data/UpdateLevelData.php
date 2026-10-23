<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Levels\Data;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The edit form; `lock_version` is the version it loaded (PreventsStaleUpdates). */
#[TypeScript]
final class UpdateLevelData extends Data
{
    public function __construct(
        public string $label,
        public ?string $description,
        public int $lock_version,
    ) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:60', Rule::unique('roles', 'label')->ignore(request()->route('level'))],
            'description' => ['nullable', 'string', 'max:255'],
            'lock_version' => ['required', 'integer', 'min:0'],
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
