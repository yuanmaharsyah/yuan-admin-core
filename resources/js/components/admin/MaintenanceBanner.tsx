import { TriangleAlert } from 'lucide-react';
import { useT } from '../../lib/i18n';

/** Full-width notice under the topbar while Settings → Akses Situs isn't open (public pages are closed). */
export function MaintenanceBanner({
    access,
}: {
    access: Yuan.AdminCore.Data.SiteAccessPageData | null;
}) {
    const t = useT();

    if (access === null) {
        return null;
    }

    return (
        <div
            role="status"
            className="flex shrink-0 items-center gap-2.5 bg-warning/12 px-4 py-2.5 text-table lg:px-8"
        >
            <TriangleAlert
                aria-hidden="true"
                className="size-4 shrink-0 text-warning"
            />
            <span>
                {t(
                    access.mode === 'maintenance'
                        ? 'site.banner_maintenance'
                        : 'site.banner_coming_soon',
                )}
                {access.message ? ` ${access.message}` : ''}
            </span>
        </div>
    );
}
