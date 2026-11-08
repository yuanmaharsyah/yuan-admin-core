import { formatNumber } from '../../lib/format';
import { Badge } from '../ui/badge';

/** "+18%" / "-5%": the change against the previous period, green up, red down, neutral at 0. */
export function ChangePill({ change }: { change: number }) {
    const sign = change > 0 ? '+' : change < 0 ? '−' : '';

    return (
        <Badge
            variant={
                change > 0
                    ? 'success'
                    : change < 0
                      ? 'destructive'
                      : 'secondary'
            }
            className="tabular-nums"
        >
            {sign}
            {formatNumber(Math.abs(change), 1)}%
        </Badge>
    );
}

/** The change pill and its caption ("dibanding periode sebelumnya"), when there is a change. */
export function ChangeLine({
    change,
    caption,
}: {
    change?: number | null;
    caption?: string | null;
}) {
    if (change === null || change === undefined) {
        return caption ? (
            <p className="m-0 text-body text-muted-foreground">{caption}</p>
        ) : null;
    }

    return (
        <p className="m-0 flex flex-wrap items-center gap-2 text-body text-muted-foreground">
            <ChangePill change={change} />
            {caption}
        </p>
    );
}
