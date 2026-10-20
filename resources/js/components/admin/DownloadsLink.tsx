import { Link, usePage, usePoll } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useT } from '../../lib/i18n';
import type { Href } from '../../lib/shell';

/**
 * The topbar's Unduhan icon with the count of finished downloads
 * (frontend-behavior §3): refreshed on every visit and every 30s (only the
 * `downloads` prop) while the tab is visible. Without a Downloads page
 * (`href`) it shows only when something is ready.
 */
export function DownloadsLink({ href }: { href?: Href }) {
    const t = useT();
    const { downloads } = usePage().props;
    usePoll(30_000, { only: ['downloads'] }, { background: 'pause' });
    const count = downloads.ready_count;

    if (!href && count === 0) {
        return null;
    }

    const content = (
        <>
            <Download aria-hidden="true" className="size-4.5" />
            {count > 0 && (
                <span
                    aria-hidden="true"
                    className="absolute top-0.5 right-0 h-4.5 min-w-4.5 rounded-full bg-primary px-1 text-center text-xs leading-4.5 font-semibold text-primary-foreground tabular-nums"
                >
                    {count}
                </span>
            )}
        </>
    );
    const className =
        'relative flex size-10 items-center justify-center rounded text-foreground hover:bg-accent';

    return href ? (
        <Link
            href={href}
            aria-label={t('shell.downloads', { count })}
            className={className}
        >
            {content}
        </Link>
    ) : (
        <span
            role="status"
            aria-label={t('shell.downloads', { count })}
            className={className}
        >
            {content}
        </span>
    );
}
