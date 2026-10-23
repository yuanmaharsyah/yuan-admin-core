import { Head, router, usePage } from '@inertiajs/react';
import { RotateCcw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '../../components/admin/DataTable';
import type { DataTableColumn } from '../../components/admin/DataTable';
import { ForceDeleteDialog } from '../../components/admin/ForceDeleteDialog';
import { PageHeader } from '../../components/admin/PageHeader';
import { useAction } from '../../hooks/useAction';
import { formatDateTime } from '../../lib/format';
import { useT } from '../../lib/i18n';

type Item = Yuan.AdminCore.Data.TrashItemData;

type Props = {
    items: Yuan.AdminCore.Data.PaginatedData<Item>;
    table: Yuan.AdminCore.Data.DataTableStateData;
    trash: Yuan.AdminCore.Data.TrashPageData;
};

/**
 * A module's Trash, Super Admin only (frontend-behavior §7; derived design:
 * the reference DataTable with restore and permanent-delete actions).
 */
export default function Index({ items, table, trash }: Props) {
    const t = useT();
    const acting = useAction();
    const { app } = usePage().props;
    const [forceDeleting, setForceDeleting] = useState<Item | null>(null);
    const title = t('trash.title', { module: trash.module_label });

    const columns: DataTableColumn<Item>[] = [
        {
            key: 'label',
            label: t('trash.label'),
            className: 'font-medium',
            hideable: false,
        },
        {
            key: 'deleted_at',
            label: t('trash.deleted_at'),
            cell: (row) => formatDateTime(row.deleted_at, app.timezone),
            className: 'w-48',
        },
    ];

    return (
        <>
            <Head title={title} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={title}
                    description={t('trash.description')}
                    backHref={trash.index_href}
                />
                <DataTable
                    data={items}
                    state={table}
                    columns={columns}
                    slug={`trash:${trash.index_href}`}
                    label={title}
                    itemLabel={t('trash.item')}
                    rowLabel={(row) => row.label}
                    rowActions={(row) => [
                        {
                            label: t('trash.restore'),
                            icon: RotateCcw,
                            onSelect: () =>
                                acting.run((done) =>
                                    router.post(
                                        trash.restore_href,
                                        { ids: [row.id] },
                                        {
                                            preserveScroll: true,
                                            onFinish: done,
                                        },
                                    ),
                                ),
                        },
                        {
                            label: t('trash.force_delete'),
                            icon: Trash2,
                            destructive: true,
                            onSelect: () => setForceDeleting(row),
                        },
                    ]}
                    empty={{
                        title: t('trash.empty_title'),
                        description: t('trash.empty_text'),
                        icon: Trash2,
                    }}
                />
            </div>
            <ForceDeleteDialog
                open={forceDeleting !== null}
                onOpenChange={(open) => !open && setForceDeleting(null)}
                itemName={forceDeleting?.label ?? ''}
                loading={acting.pending}
                onConfirm={() => {
                    if (forceDeleting) {
                        acting.run((done) =>
                            router.delete(trash.destroy_href, {
                                data: { ids: [forceDeleting.id] },
                                preserveScroll: true,
                                onFinish: () => {
                                    done();
                                    setForceDeleting(null);
                                },
                            }),
                        );
                    }
                }}
            />
        </>
    );
}
