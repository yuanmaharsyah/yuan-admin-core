import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    ArrowLeft,
    Clock,
    RotateCw,
    SearchX,
    ServerCrash,
    ShieldX,
    Wrench,
} from 'lucide-react';
import { ErrorPanel } from '../../components/admin/ErrorPanel';
import { Button } from '../../components/ui/button';
import type { MessageKey } from '../../lib/i18n';
import { useT } from '../../lib/i18n';

const MESSAGES: Record<
    number,
    { title: MessageKey; text: MessageKey; icon: LucideIcon }
> = {
    403: { title: 'errors.403.title', text: 'errors.403.text', icon: ShieldX },
    404: { title: 'errors.404.title', text: 'errors.404.text', icon: SearchX },
    419: { title: 'errors.419.title', text: 'errors.419.text', icon: Clock },
    500: {
        title: 'errors.500.title',
        text: 'errors.500.text',
        icon: ServerCrash,
    },
    503: { title: 'errors.503.title', text: 'errors.503.text', icon: Wrench },
};

/**
 * 403 / 404 / 419 / 500 / 503 (ui-design §2). 419 and 503 offer a reload;
 * every page links home (or to the login page for guests). The 502 is a
 * static page (`public/errors/502.html`): PHP is down when it shows.
 */
export default function Show(props: Yuan.AdminCore.Data.ErrorPageData) {
    const t = useT();
    const message = MESSAGES[props.status] ?? MESSAGES[500];
    const reload = props.status === 419 || props.status === 503;
    const home = (
        <Button
            asChild
            size="auth"
            variant={reload ? 'outline' : 'default'}
            className="font-semibold"
        >
            <Link href={props.home_href}>
                <ArrowLeft />
                {t(props.signed_in ? 'errors.home' : 'errors.login')}
            </Link>
        </Button>
    );

    return (
        <>
            <Head title={t(message.title)} />
            <ErrorPanel
                icon={message.icon}
                code={props.status}
                title={t(message.title)}
                text={t(message.text)}
                actions={
                    <>
                        {reload && (
                            <Button
                                size="auth"
                                className="font-semibold"
                                onClick={() => window.location.reload()}
                            >
                                <RotateCw />
                                {t('errors.reload')}
                            </Button>
                        )}
                        {home}
                    </>
                }
            />
        </>
    );
}
