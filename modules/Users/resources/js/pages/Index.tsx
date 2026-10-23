import { ConfirmDialog } from '@admin-core/components/admin/ConfirmDialog';
import { DataTable } from '@admin-core/components/admin/DataTable';
import type {
    DataTableColumn,
    RowAction,
} from '@admin-core/components/admin/DataTable';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { StatusBadge } from '@admin-core/components/admin/StatusBadge';
import { Button } from '@admin-core/components/ui/button';
import { usePermission } from '@admin-core/hooks/usePermission';
import { formatDateTime } from '@admin-core/lib/format';
import { useAction } from '@admin-core/hooks/useAction';
import { useT } from '@admin-core/lib/i18n';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2, Users as UsersIcon } from 'lucide-react';
import { useState } from 'react';
import {
    bulkDestroy,
    create,
    destroy,
    download,
    edit,
    show,
    trash,
} from '@/routes/users';

type Row = Yuan.AdminCore.Modules.Users.Data.UserListItemData;

type Props = {
    users: Yuan.AdminCore.Data.PaginatedData<Row>;
    table: Yuan.AdminCore.Data.DataTableStateData;
};

/** Users list (ui-design §4.1, reference `users-index`). */
export default function Index({ users, table }: Props) {
    const t = useT();
    const removing = useAction();
    const { app, auth } = usePage().props;
    const { can, isSuperAdmin } = usePermission();
    const [deleting, setDeleting] = useState<Row | null>(null);
    const isSelf = (row: Row) => row.id === auth.user?.id;

    const columns: DataTableColumn<Row>[] = [
        {
            key: 'name',
            label: t('users.name'),
            className: 'font-medium',
            hideable: false,
        },
        {
            key: 'username',
            label: t('users.username'),
            className: 'text-muted-foreground',
        },
        { key: 'email', label: t('users.email') },
        {
            key: 'level_label',
            label: t('users.level'),
            cell: (row) =>
                row.level_label && (
                    <span className="inline-flex h-5.5 items-center rounded border border-border px-2 text-label tracking-normal">
                        {row.level_label}
                    </span>
                ),
        },
        {
            key: 'is_active',
            label: t('users.status'),
            cell: (row) => (
                <StatusBadge
                    label={
                        row.is_active ? t('users.active') : t('users.inactive')
                    }
                    tone={row.is_active ? 'success' : 'neutral'}
                />
            ),
        },
        {
            key: 'last_login_at',
            label: t('users.last_login'),
            cell: (row) =>
                row.last_login_at ? (
                    formatDateTime(row.last_login_at, app.timezone)
                ) : (
                    <span className="text-muted-foreground">
                        {t('users.never')}
                    </span>
                ),
        },
    ];

    const rowActions = (row: Row): RowAction[] => [
        ...(can('users.view')
            ? [{ label: t('table.view'), icon: Eye, href: show(row.id) }]
            : []),
        ...(can('users.edit')
            ? [{ label: t('table.edit'), icon: Pencil, href: edit(row.id) }]
            : []),
        ...(can('users.delete') && !isSelf(row)
            ? [
                  {
                      label: t('table.delete'),
                      icon: Trash2,
                      destructive: true,
                      onSelect: () => setDeleting(row),
                  },
              ]
            : []),
    ];

    const createButton = can('users.create') && (
        <Button asChild>
            <Link href={create()}>
                <Plus />
                {t('table.create')}
            </Link>
        </Button>
    );

    return (
        <>
            <Head title={t('users.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('users.title')}
                    description={t('users.description')}
                    actions={
                        <>
                            {isSuperAdmin && (
                                <Button
                                    variant="outline"
                                    asChild
                                    className="bg-transparent"
                                >
                                    <Link href={trash()}>
                                        <Trash2 />
                                        {t('trash.link')}
                                    </Link>
                                </Button>
                            )}
                            {createButton}
                        </>
                    }
                />
                <DataTable
                    data={users}
                    state={table}
                    columns={columns}
                    slug="users"
                    label={t('users.list')}
                    itemLabel={t('users.item')}
                    rowLabel={(row) => row.name}
                    searchPlaceholder={t('users.search')}
                    rowHref={
                        can('users.view') ? (row) => show(row.id) : undefined
                    }
                    rowActions={rowActions}
                    bulkDelete={
                        can('users.delete')
                            ? (ids, onFinish) =>
                                  router.delete(bulkDestroy(), {
                                      onFinish,
                                      data: {
                                          ids: ids.filter(
                                              (id) => id !== auth.user?.id,
                                          ),
                                      },
                                      preserveScroll: true,
                                  })
                            : undefined
                    }
                    download={can('users.download') ? download() : undefined}
                    empty={{
                        icon: UsersIcon,
                        title: t('users.empty_title'),
                        description: t('users.empty_text'),
                        action: createButton,
                    }}
                />
            </div>
            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
                title={t('users.delete_title')}
                itemName={deleting?.name ?? ''}
                description={t('users.delete_text')}
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
