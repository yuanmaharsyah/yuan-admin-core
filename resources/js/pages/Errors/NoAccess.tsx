import { Head, Link } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { Button } from '../../components/ui/button';
import { useT } from '../../lib/i18n';

/** Signed in, but no module is permitted (derived from the error-page card): ask an admin, or sign out. */
export default function NoAccess(props: Yuan.AdminCore.Data.NoAccessPageData) {
    const t = useT();

    return (
        <>
            <Head title={t('errors.no_access.title')} />
            <section className="flex flex-col gap-4 rounded bg-card p-6 text-card-foreground">
                <h1 className="m-0 border-b-2 border-rule pb-3 text-card-title">
                    {t('errors.no_access.title')}
                </h1>
                <p className="m-0 text-pretty text-muted-foreground">
                    {t('errors.no_access.text')}
                </p>
                <Button asChild size="auth" className="w-full font-semibold">
                    <Link href={props.logout_href} method="post" as="button">
                        <LogOut />
                        {t('shell.logout')}
                    </Link>
                </Button>
            </section>
        </>
    );
}
