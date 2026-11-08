import { Head, Link } from '@inertiajs/react';
import { KeyRound, LogOut } from 'lucide-react';
import { ErrorPanel } from '../../components/admin/ErrorPanel';
import { Button } from '../../components/ui/button';
import { useT } from '../../lib/i18n';

/** Signed in, but no module is permitted: ask an admin, or sign out. */
export default function NoAccess(props: Yuan.AdminCore.Data.NoAccessPageData) {
    const t = useT();

    return (
        <>
            <Head title={t('errors.no_access.title')} />
            <ErrorPanel
                icon={KeyRound}
                title={t('errors.no_access.title')}
                text={t('errors.no_access.text')}
                actions={
                    <Button asChild size="auth" className="font-semibold">
                        <Link
                            href={props.logout_href}
                            method="post"
                            as="button"
                        >
                            <LogOut />
                            {t('shell.logout')}
                        </Link>
                    </Button>
                }
            />
        </>
    );
}
