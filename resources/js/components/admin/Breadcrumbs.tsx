import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Fragment } from 'react';
import { useT } from '../../lib/i18n';

/** From the `breadcrumbs` page prop only; no client-side derivation. */
export function Breadcrumbs({
    breadcrumbs,
}: {
    breadcrumbs?: Yuan.AdminCore.Data.BreadcrumbsData;
}) {
    const t = useT();

    if (!breadcrumbs?.items.length) {
        return null;
    }

    return (
        <nav
            aria-label={t('shell.breadcrumb')}
            className="flex min-w-0 items-center gap-1.5 text-table"
        >
            {breadcrumbs.items.map((crumb, index) => (
                <Fragment key={`${crumb.label}-${index}`}>
                    {index > 0 && (
                        <ChevronRight
                            aria-hidden="true"
                            className="size-3.5 shrink-0 text-muted-foreground"
                        />
                    )}
                    {crumb.href ? (
                        <Link
                            href={crumb.href}
                            className="truncate text-muted-foreground no-underline hover:text-foreground"
                        >
                            {crumb.label}
                        </Link>
                    ) : (
                        <span
                            aria-current="page"
                            className="truncate font-medium"
                        >
                            {crumb.label}
                        </span>
                    )}
                </Fragment>
            ))}
        </nav>
    );
}
