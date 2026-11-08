import { useT } from '../../lib/i18n';
import { ListCard } from './ListCard';
import { StatTile } from './StatTile';

/**
 * The right half of the auth card (≥ 1024px): a tilted, non-interactive
 * glimpse of Beranda built from our own components in dark tokens over a soft
 * grid. Fixed illustrative figures; screen readers skip it.
 */
export function AuthShowcase() {
    const t = useT();

    return (
        <div
            aria-hidden="true"
            inert
            className="dark auth-grid relative hidden overflow-hidden bg-background text-foreground lg:block"
        >
            <div className="absolute inset-0 flex items-center justify-center p-10">
                <div className="flex w-[34rem] origin-center -rotate-6 flex-col gap-4 opacity-95 select-none">
                    <div className="grid grid-cols-2 gap-4">
                        <StatTile
                            icon="wallet"
                            label={t('auth.showcase_revenue')}
                            value={395700000}
                            format="currency"
                            change={12.4}
                        />
                        <StatTile
                            icon="file-plus"
                            label={t('auth.showcase_invoices')}
                            value={714}
                            change={8}
                        />
                    </div>
                    <ListCard
                        title={t('auth.showcase_latest')}
                        empty=""
                        rows={[
                            {
                                key: '1',
                                title: 'INV-000714',
                                subtitle: 'Toko Sinar Jaya',
                                value: 'Rp 12.500.000',
                                icon: 'receipt',
                                tone: 'success',
                                href: null,
                                change: null,
                                progress: null,
                            },
                            {
                                key: '2',
                                title: 'INV-000713',
                                subtitle: 'CV Maju Bersama',
                                value: 'Rp 4.250.000',
                                icon: 'receipt',
                                tone: 'neutral',
                                href: null,
                                change: null,
                                progress: null,
                            },
                            {
                                key: '3',
                                title: 'INV-000712',
                                subtitle: 'PT Nusa Abadi',
                                value: 'Rp 870.000',
                                icon: 'receipt',
                                tone: 'neutral',
                                href: null,
                                change: null,
                                progress: null,
                            },
                        ]}
                    />
                </div>
            </div>
            <div className="absolute inset-x-0 bottom-0 h-1/3 bg-linear-to-t from-background to-transparent" />
        </div>
    );
}
