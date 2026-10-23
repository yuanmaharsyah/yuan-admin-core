import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { PermissionMatrix } from '@admin-core/components/admin/PermissionMatrix';
import { useAction } from '@admin-core/hooks/useAction';
import { useT } from '@admin-core/lib/i18n';
import { Head, router } from '@inertiajs/react';
import { index } from '@/routes/levels';
import { update as savePermissions } from '@/routes/levels/permissions';

/** One level's Hak Akses (reference `level-perm`); one PUT with the whole matrix. */
export default function Permissions({
    matrix,
}: {
    matrix: Yuan.AdminCore.Data.PermissionMatrixData;
}) {
    const t = useT();
    const saving = useAction();
    const title = t('levels.permissions_title', { name: matrix.level.label });

    return (
        <>
            <Head title={title} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={title}
                    description={t('levels.permissions_text')}
                    backHref={index()}
                />
                <PermissionMatrix
                    key={JSON.stringify(matrix.rows.map((row) => row.granted))}
                    matrix={matrix}
                    processing={saving.pending}
                    onSave={(grants) =>
                        saving.run((done) =>
                            router.put(
                                savePermissions(matrix.level.id),
                                { grants },
                                { preserveScroll: true, onFinish: done },
                            ),
                        )
                    }
                />
            </div>
        </>
    );
}
