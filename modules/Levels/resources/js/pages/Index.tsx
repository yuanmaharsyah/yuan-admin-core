import { ConfirmDialog } from '@admin-core/components/admin/ConfirmDialog';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { Button } from '@admin-core/components/ui/button';
import { formatNumber } from '@admin-core/lib/format';
import { useAction } from '@admin-core/hooks/useAction';
import { useT } from '@admin-core/lib/i18n';
import { Head, Link, router } from '@inertiajs/react';
import { Lock, Pencil, Plus, ShieldCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { create, destroy, edit, permissions } from '@/routes/levels';

type Row = Yuan.AdminCore.Modules.Levels.Data.LevelListItemData;

/** Levels (ui-design §4.2, reference `levels`): the protected level has no actions. */
export default function Index({
    levels,
}: {
    levels: Yuan.AdminCore.Modules.Levels.Data.LevelListData;
}) {
    const t = useT();
    const removing = useAction();
    const [deleting, setDeleting] = useState<Row | null>(null);

    return (
        <>
            <Head title={t('levels.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('levels.title')}
                    description={t('levels.description')}
                    actions={
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                {t('table.create')}
                            </Link>
                        </Button>
                    }
                />
                <section
                    aria-label={t('levels.list')}
                    className="overflow-x-auto rounded bg-card text-card-foreground"
                >
                    <table className="w-full min-w-2xl border-collapse text-table">
                        <thead>
                            <tr className="h-10 border-b border-rule text-left text-label text-muted-foreground uppercase">
                                <th scope="col" className="px-3">
                                    {t('levels.name')}
                                </th>
                                <th scope="col" className="px-3">
                                    {t('levels.desc')}
                                </th>
                                <th scope="col" className="px-3 text-right">
                                    {t('levels.users')}
                                </th>
                                <th scope="col" className="w-px px-3">
                                    <span className="sr-only">
                                        {t('table.actions')}
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {levels.items.map((level) => (
                                <tr
                                    key={level.id}
                                    className="h-row border-b border-border last:border-b-0"
                                >
                                    <td className="px-3 font-medium">
                                        <span className="inline-flex items-center gap-2">
                                            {level.is_protected && (
                                                <Lock
                                                    aria-label={t(
                                                        'levels.protected',
                                                    )}
                                                    className="size-3.5 text-muted-foreground"
                                                />
                                            )}
                                            {level.label}
                                        </span>
                                    </td>
                                    <td className="px-3 text-muted-foreground">
                                        {level.description}
                                    </td>
                                    <td className="px-3 text-right tabular-nums">
                                        {formatNumber(level.users_count, 0)}
                                    </td>
                                    <td className="px-2">
                                        {!level.is_protected && (
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="compact"
                                                    asChild
                                                >
                                                    <Link
                                                        href={permissions(
                                                            level.id,
                                                        )}
                                                    >
                                                        <ShieldCheck />
                                                        {t(
                                                            'levels.permissions',
                                                        )}
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(level.id)}
                                                        aria-label={`${t('table.edit')} ${level.label}`}
                                                    >
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-destructive hover:text-destructive"
                                                    aria-label={`${t('table.delete')} ${level.label}`}
                                                    onClick={() =>
                                                        setDeleting(level)
                                                    }
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </section>
            </div>
            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
                title={t('levels.delete_title')}
                itemName={deleting?.label ?? ''}
                description={t('levels.delete_text')}
                loading={removing.pending}
                onConfirm={() => {
                    if (deleting) {
                        removing.run((done) =>
                            router.delete(destroy(deleting.id), {
                                preserveScroll: true,
                                onFinish: () => {
                                    done();
                                    setDeleting(null);
                                },
                            }),
                        );
                    }
                }}
            />
        </>
    );
}
