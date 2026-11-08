import type { ReactNode } from 'react';

/** A card's title (18px/500) with an optional muted subtitle and an action on the right. */
export function CardHeading({
    title,
    subtitle,
    action,
}: {
    title: string;
    subtitle?: ReactNode;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-start gap-x-4 gap-y-2">
            <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                <h2 className="m-0 text-card-title">{title}</h2>
                {subtitle && (
                    <p className="m-0 text-body text-muted-foreground">
                        {subtitle}
                    </p>
                )}
            </div>
            {action}
        </div>
    );
}
