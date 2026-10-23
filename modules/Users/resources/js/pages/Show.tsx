import { ConfirmDialog } from '@admin-core/components/admin/ConfirmDialog';
import { DetailGrid } from '@admin-core/components/admin/DetailGrid';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { StatusBadge } from '@admin-core/components/admin/StatusBadge';
import { Button } from '@admin-core/components/ui/button';
import { usePermission } from '@admin-core/hooks/usePermission';
import { formatDateTime } from '@admin-core/lib/format';
import { useT } from '@admin-core/lib/i18n';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { KeyRound, Pencil, Power, PowerOff, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { activate, deactivate, destroy, edit, index } from '@/routes/users';
import { ResetPasswordDialog } from '../components/ResetPasswordDialog';
import { TemporaryPasswordDialog } from '../components/TemporaryPasswordDialog';

type Props = {
    user: Yuan.AdminCore.Modules.Users.Data.UserData;
    options: Yuan.AdminCore.Modules.Users.Data.UserFormOptionsData;
};

/**
 * A user (approved show pattern). Reset password, activate / deactivate and
 * delete are hidden on your own account (frontend-behavior §8.1).
 */
export default function Show({ user, options }: Props) {
    const t = useT();
    const page = usePage();
    const { app } = page.props;
    const { flash } = page;
    const { can } = usePermission();
    const [dialog, setDialog] = useState<
        'reset' | 'deactivate' | 'delete' | null
    >(null);
    const [shownSecret, setShownSecret] = useState<string | null>(null);
    const secret =
        flash.secret && flash.secret !== shownSecret ? flash.secret : null;
    const manage = can('users.edit') && !user.is_self;

    return (
        <>
            <Head title={user.name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={user.name}
                    description={user.email}
                    backHref={index()}
                    actions={
                        <>
                            {can('users.edit') && (
                                <Button asChild>
                                    <Link href={edit(user.id)}>
                                        <Pencil />
                                        {t('table.edit')}
                                    </Link>
                                </Button>
                            )}
                            {manage && (
                                <>
                                    <Button
                                        variant="outline"
                                        className="bg-transparent"
                                        onClick={() => setDialog('reset')}
                                    >
                                        <KeyRound />
                                        {t('users.reset_password')}
                                    </Button>
                                    {user.is_active ? (
                                        <Button
                                            variant="outline"
                                            className="bg-transparent"
                                            onClick={() =>
                                                setDialog('deactivate')
                                            }
                                        >
                                            <PowerOff />
                                            {t('users.deactivate')}
                                        </Button>
                                    ) : (
                                        <Button
                                            variant="outline"
                                            className="bg-transparent"
                                            onClick={() =>
                                                router.post(
                                                    activate(user.id),
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Power />
                                            {t('users.activate')}
                                        </Button>
                                    )}
                                </>
                            )}
                            {can('users.delete') && !user.is_self && (
                                <Button
                                    variant="outline"
                                    className="border-destructive bg-transparent text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    onClick={() => setDialog('delete')}
                                >
                                    <Trash2 />
                                    {t('table.delete')}
                                </Button>
                            )}
                        </>
                    }
                />
                <section
                    aria-label={t('detail.title')}
                    className="rounded bg-card p-6 text-card-foreground"
                >
                    <DetailGrid
                        items={[
                            {
                                label: t('users.username'),
                                value: user.username,
                            },
                            { label: t('users.email'), value: user.email },
                            {
                                label: t('users.level'),
                                value: user.level_label,
                            },
                            {
                                label: t('users.status'),
                                value: (
                                    <StatusBadge
                                        label={
                                            user.is_active
                                                ? t('users.active')
                                                : t('users.inactive')
                                        }
                                        tone={
                                            user.is_active
                                                ? 'success'
                                                : 'neutral'
                                        }
                                    />
                                ),
                            },
                            {
                                label: t('users.last_login'),
                                value: user.last_login_at
                                    ? formatDateTime(
                                          user.last_login_at,
                                          app.timezone,
                                      )
                                    : t('users.never'),
                            },
                            {
                                label: t('users.created'),
                                value:
                                    user.created_at &&
                                    formatDateTime(
                                        user.created_at,
                                        app.timezone,
                                    ),
                            },
                            {
                                label: t('users.must_change'),
                                value: user.must_change_password
                                    ? t('users.yes')
                                    : t('users.no'),
                            },
                            ...user.extra.map((field) => ({
                                label: field.label,
                                value: field.display,
                            })),
                        ]}
                    />
                </section>
            </div>
            <ResetPasswordDialog
                open={dialog === 'reset'}
                onOpenChange={(open) => setDialog(open ? 'reset' : null)}
                user={user}
                passwordHint={options.password_hint}
            />
            <TemporaryPasswordDialog
                password={secret}
                onClose={() => setShownSecret(secret)}
            />
            <ConfirmDialog
                open={dialog === 'deactivate'}
                onOpenChange={(open) => setDialog(open ? 'deactivate' : null)}
                title={t('users.deactivate_title')}
                itemName={user.name}
                description={t('users.deactivate_text')}
                confirmLabel={t('users.deactivate')}
                onConfirm={() => {
                    setDialog(null);
                    router.post(
                        deactivate(user.id),
                        {},
                        { preserveScroll: true },
                    );
                }}
            />
            <ConfirmDialog
                open={dialog === 'delete'}
                onOpenChange={(open) => setDialog(open ? 'delete' : null)}
                title={t('users.delete_title')}
                itemName={user.name}
                description={t('users.delete_text')}
                onConfirm={() => {
                    setDialog(null);
                    router.delete(destroy(user.id));
                }}
            />
        </>
    );
}
