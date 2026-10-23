<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * Logs created / updated / deleted / restored / force_deleted for a module
 * model (spec §7.1): dirty fillable attributes only, `log_name` = module slug,
 * `$hidden`, `omit` and encrypted-cast fields never stored (the trait would
 * read them decrypted), `partial` / `full` fields masked.
 *
 * @mixin Model
 */
trait LogsModuleActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $masker = app(Masker::class);

        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept([
                ...$this->getHidden(),
                ...array_filter($this->getFillable(), fn (string $field): bool => $masker->rule($field) === 'omit'),
                ...array_keys(array_filter($this->getCasts(), fn (string $cast): bool => str_starts_with($cast, 'encrypted') || str_contains($cast, 'AsEncrypted'))),
            ])
            ->useLogName($this->auditLogName());
    }

    /** The module's slug; a core model outside a module (Level) names its log itself. */
    protected function auditLogName(): ?string
    {
        return app(ModuleRegistry::class)->forClass(static::class)?->slug;
    }

    /**
     * What a person recognises the record by; stored with each row so it stays
     * readable after the record is deleted.
     */
    public function auditLabel(): string
    {
        foreach (['name', 'title', 'number'] as $attribute) {
            $value = $this->getAttributes()[$attribute] ?? null;

            if (is_scalar($value) && $value !== '') {
                return (string) $value;
            }
        }

        return '#'.$this->getKey();
    }
}
