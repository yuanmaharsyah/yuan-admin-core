<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One uploaded file, checked against the record model's attachmentRules(). */
#[TypeScript]
final class UploadAttachmentData extends Data
{
    public function __construct(
        public UploadedFile $file,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        $model = request()->route()?->defaults['model'] ?? null;
        $instance = is_string($model) && is_subclass_of($model, Model::class) ? new $model : null;
        // No model (or no HasAttachments): nothing passes.
        $rules = $instance !== null && method_exists($instance, 'attachmentRules') ? $instance->attachmentRules() : ['mimes' => ['none'], 'max_kb' => 0];

        return ['file' => ['required', 'file', 'mimes:'.implode(',', $rules['mimes']), 'max:'.$rules['max_kb']]];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return ['file' => __('berkas')];
    }
}
