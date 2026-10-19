<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Activitylog\Support\CauserResolver;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * The only way to write audit events that aren't model changes (spec §7.1).
 * Rows are written synchronously, inside the caller's transaction.
 */
final class Audit
{
    private static ?string $batch = null;

    /**
     * @param  array<string, mixed>  $meta
     *
     * @throws InvalidArgumentException When the event is neither a taxonomy event nor a custom action of the module.
     */
    public static function record(AuditEvent|string $event, ?Model $subject = null, array $meta = [], ?string $module = null): void
    {
        $registry = app(ModuleRegistry::class);
        $definition = $module !== null ? $registry->find($module) : ($subject === null ? null : $registry->forClass($subject::class));
        $case = $event instanceof AuditEvent ? $event : AuditEvent::tryFrom($event);
        $name = $case === null ? $event : $case->value;

        if ($case === null && ! in_array($name, $definition?->customActions() ?? [], true)) {
            throw new InvalidArgumentException("Unknown audit event [{$name}]: use an AuditEvent case or a custom action declared by the module.");
        }

        $logger = activity()
            ->useLog($case?->logName() ?? $module ?? $definition->slug ?? 'system')
            ->event($name)
            ->withProperties(['meta' => $meta]);

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        $logger->log($name);
    }

    /**
     * Every row written inside the callback shares one `batch_uuid`. A nested
     * batch joins the outer one.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public static function batch(Closure $callback): mixed
    {
        if (self::$batch !== null) {
            return $callback();
        }

        self::$batch = (string) Str::uuid();

        try {
            return $callback();
        } finally {
            self::$batch = null;
        }
    }

    public static function batchUuid(): ?string
    {
        return self::$batch;
    }

    /**
     * Rows written inside the callback have this causer. Jobs use it with the
     * user they were dispatched for, so the causer is never null.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public static function actingAs(Model $causer, Closure $callback): mixed
    {
        return app(CauserResolver::class)->withCauser($causer, $callback);
    }
}
