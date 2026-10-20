import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { useT } from '../../lib/i18n';
import type { Href } from '../../lib/shell';

type Props = {
    title: string;
    description?: string;
    actions?: ReactNode;
    backHref?: Href;
};

/** Page title (28px/700), actions on the same line, then the 2px ink rule. */
export function PageHeader({ title, description, actions, backHref }: Props) {
    const t = useT();

    return (
        <div className="flex flex-col gap-1.5 border-b-2 border-rule pb-4">
            <div className="flex flex-wrap items-center gap-4">
                {backHref && (
                    <Link
                        href={backHref}
                        aria-label={t('common.back')}
                        className="flex size-control items-center justify-center rounded hover:bg-accent"
                    >
                        <ArrowLeft className="size-5" />
                    </Link>
                )}
                <h1 className="m-0 min-w-0 flex-auto text-title">{title}</h1>
                {actions && (
                    <div className="flex items-center gap-2">{actions}</div>
                )}
            </div>
            {description && (
                <p className="m-0 text-muted-foreground">{description}</p>
            )}
        </div>
    );
}
