<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Account;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A new profile photo. */
#[TypeScript]
final class UpdateAvatarData extends Data
{
    public function __construct(
        public UploadedFile $avatar,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return ['avatar' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:1024']];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return ['avatar' => __('foto profil')];
    }
}
