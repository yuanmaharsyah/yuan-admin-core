import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { PermissionMatrix } from '@admin-core/components/admin/PermissionMatrix';
import { useT } from '@admin-core/lib/i18n';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { index } from '@/routes/levels';
import { update as savePermissions } from '@/routes/levels/permissions';

/** One level's Hak Akses (reference `level-perm`); one PUT with the whole matrix. */
export default function Permissions({
    matrix,
}: {
    matrix: Yuan.AdminCore.Data.PermissionMatrixData;
}) {
    const t = useT();
    const [processing, setProcessing] = useState(false);
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
                    processing={processing}
                    onSave={(grants) =>
                        router.put(
                            savePermissions(matrix.level.id),
                            { grants },
                            {
                                preserveScroll: true,
                                onStart: () => setProcessing(true),
                                onFinish: () => setProcessing(false),
                            },
                        )
                    }
                />
            </div>
        </>
    );
}
