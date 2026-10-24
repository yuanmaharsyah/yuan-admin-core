import { cn } from '../../lib/utils';

/** A grey placeholder block while data loads (the DataTable's skeleton style). */
function Skeleton({ className }: { className?: string }) {
    return (
        <div aria-hidden="true" className={cn('rounded bg-muted', className)} />
    );
}

export { Skeleton };
