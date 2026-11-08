import { Head } from '@inertiajs/react';
import { Rocket, Wrench } from 'lucide-react';
import { ErrorPanel } from '../../components/admin/ErrorPanel';
import { useT } from '../../lib/i18n';

/** The public page while Settings → Akses Situs is maintenance (503) or coming soon (noindex). */
export default function SiteAccess(
    props: Yuan.AdminCore.Data.SiteAccessPageData,
) {
    const t = useT();
    const maintenance = props.mode === 'maintenance';
    const title = t(
        maintenance ? 'site.maintenance_title' : 'site.coming_soon_title',
    );

    return (
        <>
            <Head title={title}>
                {!maintenance && <meta name="robots" content="noindex" />}
            </Head>
            <ErrorPanel
                icon={maintenance ? Wrench : Rocket}
                title={title}
                text={
                    props.message ??
                    t(
                        maintenance
                            ? 'site.maintenance_text'
                            : 'site.coming_soon_text',
                    )
                }
            />
        </>
    );
}
