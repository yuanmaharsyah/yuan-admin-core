<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Yuan\AdminCore\DataTable\HasVisibilityScope;

/**
 * A relation field (`customer_id`) must point at a live record the signed-in
 * user may see: `$model::query()->visibleTo($user)`, so a project's "own unit
 * only" rule also guards what can be linked, not just what is listed. Use it
 * instead of `exists:` for every foreign key a form submits.
 */
final readonly class VisibleRecord implements ValidationRule
{
    /**
     * @param  class-string<Model>  $model  A model using HasVisibilityScope.
     */
    public function __construct(private string $model)
    {
        if (! in_array(HasVisibilityScope::class, class_uses_recursive($model), true)) {
            throw new InvalidArgumentException("{$model} does not use HasVisibilityScope.");
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = $this->model::query();
        $model = $query->getModel();

        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            $fail('validation.exists')->translate();

            return;
        }

        // The `visibleTo` scope, called directly: the class-string isn't typed as using the trait.
        if (method_exists($model, 'scopeVisibleTo')) {
            $model->scopeVisibleTo($query, auth()->user());
        }

        if (! $query->whereKey($value)->exists()) {
            $fail('validation.exists')->translate();
        }
    }
}
