import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { formatDateTime } from '../../lib/format';
import { useT } from '../../lib/i18n';
import { Sheet, SheetContent, SheetDescription, SheetTitle } from '../ui/sheet';
import { DiffTable } from './DiffTable';
import { StatusBadge } from './StatusBadge';

type Detail = Yuan.AdminCore.Data.Audit.ActivityDetailData;

type Props = {
    /** The open row (`?event_id=`); null closes the drawer. */
    detail: Detail | null;
    onClose: () => void;
};

/**
 * One audit row in full (reference `ActivityDrawer`): who, when, from where,
 * what changed (or the stored details), and links to the record and to the
 * rest of its batch. Esc and the backdrop close it.
 */
export function ActivityDrawer({ detail, onClose }: Props) {
    const t = useT();
    const { app } = usePage().props;
    const item = detail?.item;

    return (
        <Sheet
            open={detail !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <SheetContent
                aria-label={t('activity.drawer_label')}
                className="w-full gap-0 overflow-y-auto bg-card p-0 sm:max-w-120"
            >
                {detail && item && (
                    <>
                        <div className="flex flex-col gap-2 border-b border-border p-6 pr-12">
                            <div>
                                <StatusBadge
                                    tone={item.tone}
                                    label={item.event_label}
                                />
                            </div>
                            <SheetTitle className="text-section">
                                {item.subject_label ??
                                    item.attempted_username ??
                                    item.module_label}
                            </SheetTitle>
                            <SheetDescription className="text-table">
                                {[item.subject_type, item.module_label]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </SheetDescription>
                        </div>
                        <dl className="grid grid-cols-[8rem_1fr] gap-x-4 gap-y-2 border-b border-border p-6 text-table">
                            <dt className="text-muted-foreground">
                                {t('activity.time')}
                            </dt>
                            <dd className="tabular-nums">
                                {formatDateTime(item.created_at, app.timezone)}
                            </dd>
                            {detail.context.map((row) => (
                                <div key={row.label} className="contents">
                                    <dt className="text-muted-foreground">
                                        {row.label}
                                    </dt>
                                    <dd
                                        className={
                                            row.mono
                                                ? 'font-mono break-all'
                                                : 'break-words'
                                        }
                                    >
                                        {row.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                        <section className="flex flex-col gap-3 p-6">
                            <h3 className="text-card-title">
                                {detail.diff.length > 0
                                    ? t('activity.changes')
                                    : t('activity.notes')}
                            </h3>
                            {detail.diff.length > 0 ? (
                                <DiffTable rows={detail.diff} />
                            ) : detail.notes.length > 0 ? (
                                <dl className="grid grid-cols-[8rem_1fr] gap-x-4 gap-y-2 text-table">
                                    {detail.notes.map((note) => (
                                        <div
                                            key={note.label}
                                            className="contents"
                                        >
                                            <dt className="text-muted-foreground">
                                                {note.label}
                                            </dt>
                                            <dd className="break-words whitespace-pre-wrap">
                                                {note.value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            ) : (
                                <p className="text-table text-muted-foreground">
                                    {t('activity.no_changes')}
                                </p>
                            )}
                            {item.batch_size !== null &&
                                item.batch_size > 1 && (
                                    <p className="text-table text-muted-foreground italic">
                                        {t('activity.batch', {
                                            count: item.batch_size,
                                        })}
                                    </p>
                                )}
                            <div className="flex flex-col gap-2 pt-2">
                                {detail.batch_href && (
                                    <Link
                                        href={detail.batch_href}
                                        className="flex items-center gap-1.5 text-table font-medium underline-offset-4 hover:underline"
                                    >
                                        {t('activity.open_batch')}
                                        <ArrowRight
                                            aria-hidden="true"
                                            className="size-3.5"
                                        />
                                    </Link>
                                )}
                                {detail.subject_href && (
                                    <Link
                                        href={detail.subject_href}
                                        className="flex items-center gap-1.5 text-table font-medium underline-offset-4 hover:underline"
                                    >
                                        {t('activity.open_record')}
                                        <ArrowRight
                                            aria-hidden="true"
                                            className="size-3.5"
                                        />
                                    </Link>
                                )}
                            </div>
                        </section>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
