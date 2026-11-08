import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { useT } from '../../lib/i18n';
import type { Href } from '../../lib/shell';
import { Breadcrumbs } from './Breadcrumbs';

type Props = {
    title: string;
    description?: string;
    actions?: ReactNode;
    backHref?: Href;
};

/** Breadcrumbs (from the page prop), then the title (24px/600) with its actions on the same line and the description. */
export function PageHeader({ title, description, actions, backHref }: Props) {
    const t = useT();
    const { breadcrumbs } = usePage().props;

    return (
        <div className="flex flex-col gap-1">
            <div className="mb-1 hidden empty:hidden lg:block">
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex flex-wrap items-center gap-4">
                {backHref && (
                    <Link
                        href={backHref}
                        aria-label={t('common.back')}
                        className="flex size-control items-center justify-center rounded transition-colors duration-150 hover:bg-accent"
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
                <p className="m-0 text-body text-muted-foreground">
                    {description}
                </p>
            )}
        </div>
    );
}
