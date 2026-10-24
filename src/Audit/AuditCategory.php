<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

/**
 * The five groups of "Aktivitas per Jenis" (frontend-behavior §8.3, reference
 * `audit`): `filter[event]` on the Log Aktivitas takes one of these. Custom
 * module actions fall under Lainnya.
 */
enum AuditCategory: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Auth = 'auth';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Dibuat',
            self::Updated => 'Diubah',
            self::Deleted => 'Dihapus',
            self::Auth => 'Autentikasi',
            self::Other => 'Lainnya',
        };
    }

    /**
     * The event names of this group; empty for Other, which is "none of the rest".
     *
     * @return list<string>
     */
    public function events(): array
    {
        return array_values(array_map(
            fn (AuditEvent $event): string => $event->value,
            array_filter(AuditEvent::cases(), fn (AuditEvent $event): bool => $this !== self::Other && $event->category() === $this),
        ));
    }

    public static function of(string $event): self
    {
        return AuditEvent::tryFrom($event)?->category() ?? self::Other;
    }
}
