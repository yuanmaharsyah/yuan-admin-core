import { cn } from '../../lib/utils';

export type StatusTone =
    'neutral' | 'success' | 'warning' | 'info' | 'destructive';

const tones: Record<StatusTone, string> = {
    neutral: 'bg-muted text-muted-foreground',
    success: 'bg-success/12 text-success',
    warning: 'bg-warning/12 text-warning',
    info: 'bg-info/12 text-info',
    destructive: 'bg-destructive/12 text-destructive',
};

/** A soft status pill with a dot; the tone carries the meaning, never yellow. */
export function StatusBadge({
    label,
    tone = 'neutral',
}: {
    label: string;
    tone?: StatusTone;
}) {
    return (
        <span
            className={cn(
                'inline-flex h-5.5 items-center gap-1.5 rounded px-2 text-label tracking-normal whitespace-nowrap',
                tones[tone],
            )}
        >
            <span
                aria-hidden="true"
                className="size-1.5 rounded-full bg-current"
            />
            {label}
        </span>
    );
}
