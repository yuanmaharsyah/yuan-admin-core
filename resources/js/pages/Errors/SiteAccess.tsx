import { Head } from '@inertiajs/react';
import { useT } from '../../lib/i18n';

/** The public page while Settings → Akses Situs is maintenance (503) or coming soon (noindex); derived from the error-page card. */
export default function SiteAccess(
    props: Yuan.AdminCore.Data.SiteAccessPageData,
) {
    const t = useT();
    const title = t(
        props.mode === 'maintenance'
            ? 'site.maintenance_title'
            : 'site.coming_soon_title',
    );

    return (
        <>
            <Head title={title}>
                {props.mode === 'coming_soon' && (
                    <meta name="robots" content="noindex" />
                )}
            </Head>
            <section className="flex flex-col gap-4 rounded bg-card p-6 text-card-foreground">
                <h1 className="m-0 border-b-2 border-rule pb-3 text-card-title">
                    {title}
                </h1>
                <p className="m-0 text-pretty whitespace-pre-line text-muted-foreground">
                    {props.message ??
                        t(
                            props.mode === 'maintenance'
                                ? 'site.maintenance_text'
                                : 'site.coming_soon_text',
                        )}
                </p>
            </section>
        </>
    );
}
