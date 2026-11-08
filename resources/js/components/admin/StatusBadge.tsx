import { Badge } from '../ui/badge';

export type StatusTone =
    'neutral' | 'success' | 'warning' | 'info' | 'destructive';

const variants = {
    neutral: 'secondary',
    success: 'success',
    warning: 'warning',
    info: 'info',
    destructive: 'destructive',
} as const;

/** A soft status pill with a dot (the `Badge` primitive); the tone carries the meaning, never yellow. */
export function StatusBadge({
    label,
    tone = 'neutral',
}: {
    label: string;
    tone?: StatusTone;
}) {
    return (
        <Badge
            variant={variants[tone]}
            className={tone === 'neutral' ? 'text-muted-foreground' : undefined}
        >
            <span
                aria-hidden="true"
                className="size-1.5 rounded-full bg-current"
            />
            {label}
        </Badge>
    );
}
