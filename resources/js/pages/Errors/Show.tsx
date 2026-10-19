import { Head, Link } from '@inertiajs/react';
import { RotateCw } from 'lucide-react';
import { Button } from '../../components/ui/button';
import type { MessageKey } from '../../lib/i18n';
import { useT } from '../../lib/i18n';

const MESSAGES: Record<number, { title: MessageKey; text: MessageKey }> = {
    403: { title: 'errors.403.title', text: 'errors.403.text' },
    404: { title: 'errors.404.title', text: 'errors.404.text' },
    419: { title: 'errors.419.title', text: 'errors.419.text' },
    500: { title: 'errors.500.title', text: 'errors.500.text' },
    503: { title: 'errors.503.title', text: 'errors.503.text' },
};

/**
 * 403 / 404 / 419 / 500 / 503 (derived design: the auth card with the code in
 * ink over the 2px rule). 419 and 503 offer a reload; the others link home
 * (or to the login page for guests).
 */
export default function Show(props: Yuan.AdminCore.Data.ErrorPageData) {
    const t = useT();
    const message = MESSAGES[props.status] ?? MESSAGES[500];
    const reload = props.status === 419 || props.status === 503;

    return (
        <>
            <Head title={t(message.title)} />
            <section className="flex flex-col gap-4 rounded bg-card p-6 text-card-foreground">
                <div className="flex flex-col gap-2 border-b-2 border-rule pb-3">
                    <span className="text-6xl leading-none font-bold tabular-nums">
                        {props.status}
                    </span>
                    <h1 className="m-0 text-card-title">{t(message.title)}</h1>
                </div>
                <p className="m-0 text-pretty text-muted-foreground">
                    {t(message.text)}
                </p>
                {reload ? (
                    <Button
                        size="auth"
                        className="w-full font-semibold"
                        onClick={() => window.location.reload()}
                    >
                        <RotateCw />
                        {t('errors.reload')}
                    </Button>
                ) : (
                    <Button
                        asChild
                        size="auth"
                        className="w-full font-semibold"
                    >
                        <Link href={props.home_href}>
                            {t(
                                props.signed_in
                                    ? 'errors.home'
                                    : 'errors.login',
                            )}
                        </Link>
                    </Button>
                )}
            </section>
        </>
    );
}
