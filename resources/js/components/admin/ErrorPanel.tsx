import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * The body of the error and site-access pages (ui-design §2 "Error pages"):
 * a status icon in a soft circle, the large code, title, text and actions,
 * centered in AuthLayout's card.
 */
export function ErrorPanel({
    icon: Icon,
    code,
    title,
    text,
    actions,
}: {
    icon: LucideIcon;
    code?: number;
    title: string;
    text: string;
    actions?: ReactNode;
}) {
    return (
        <section className="flex flex-col items-center gap-5 py-4 text-center">
            <span
                aria-hidden="true"
                className="flex size-20 items-center justify-center rounded-full bg-muted"
            >
                <span className="flex size-12 items-center justify-center rounded-full border border-border bg-card">
                    <Icon className="size-6" />
                </span>
            </span>
            {code !== undefined && (
                <span className="text-7xl leading-none font-semibold tracking-tight tabular-nums">
                    {code}
                </span>
            )}
            <div className="flex flex-col gap-2">
                <h1 className="m-0 text-title">{title}</h1>
                <p className="m-0 max-w-sm text-pretty whitespace-pre-line text-muted-foreground">
                    {text}
                </p>
            </div>
            {actions && (
                <div className="flex w-full flex-wrap justify-center gap-3">
                    {actions}
                </div>
            )}
        </section>
    );
}
