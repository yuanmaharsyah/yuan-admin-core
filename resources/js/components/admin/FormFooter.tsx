import { Link } from '@inertiajs/react';
import { useT } from '../../lib/i18n';
import type { Href } from '../../lib/shell';
import { Button } from '../ui/button';

type Props = {
    cancelHref: Href;
    processing: boolean;
    dirty: boolean;
};

/** The sticky Batal / Simpan bar at the bottom of a form (ui-design §3.2). */
export function FormFooter({ cancelHref, processing, dirty }: Props) {
    const t = useT();

    return (
        <div className="sticky bottom-0 z-5 flex items-center gap-3 rounded border border-border bg-card px-4 py-3">
            <span
                role="status"
                className="min-w-0 flex-1 text-table text-muted-foreground"
            >
                {dirty ? t('form.dirty') : null}
            </span>
            <Button
                variant="outline"
                asChild
                className="bg-transparent font-semibold"
            >
                <Link href={cancelHref}>{t('form.cancel')}</Link>
            </Button>
            <Button
                type="submit"
                disabled={processing}
                className="font-semibold"
            >
                {processing ? t('form.saving') : t('form.save')}
            </Button>
        </div>
    );
}
