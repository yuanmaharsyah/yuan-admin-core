import { EmptyState } from '@admin-core/components/admin/EmptyState';
import { Icon } from '@admin-core/components/admin/Icon';
import { PageHeader } from '@admin-core/components/admin/PageHeader';
import { WidgetGrid } from '@admin-core/components/admin/WidgetGrid';
import { Button } from '@admin-core/components/ui/button';
import { useT } from '@admin-core/lib/i18n';
import { Head, Link, router, usePage } from '@inertiajs/react';

type Page = Yuan.AdminCore.Modules.Dashboard.Data.DashboardPageData;

const RANGES = [
    ['7d', 7],
    ['30d', 30],
    ['90d', 90],
] as const;

function greeting(hour: number) {
    if (hour < 11) {
        return 'dashboard.morning';
    }

    if (hour < 15) {
        return 'dashboard.noon';
    }

    return hour < 18 ? 'dashboard.afternoon' : 'dashboard.evening';
}

/**
 * Beranda (ui-design §4.8, reference `beranda`): greeting, the range, and the
 * permitted widgets. A range change reloads only the widgets that use it.
 */
export default function Index({ dashboard }: { dashboard: Page }) {
    const t = useT();
    const { auth, menu } = usePage().props;
    const name = auth.user?.name ?? '';
    const ranged = dashboard.widgets
        .filter((widget) => widget.uses_range)
        .map((widget) => widget.prop);

    return (
        <>
            <Head title={t('dashboard.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('dashboard.title')}
                    description={t(greeting(new Date().getHours()), { name })}
                    actions={
                        ranged.length > 0 && (
                            <div
                                role="group"
                                aria-label={t('dashboard.range')}
                                className="flex gap-1"
                            >
                                {RANGES.map(([key, days]) => (
                                    <Button
                                        key={key}
                                        size="sm"
                                        variant={
                                            dashboard.range === key
                                                ? 'default'
                                                : 'outline'
                                        }
                                        aria-pressed={dashboard.range === key}
                                        onClick={() =>
                                            router.reload({
                                                data: { range: key },
                                                only: ['dashboard', ...ranged],
                                            })
                                        }
                                    >
                                        {t('dashboard.days', {
                                            days: String(days),
                                        })}
                                    </Button>
                                ))}
                            </div>
                        )
                    }
                />
                {dashboard.widgets.length > 0 ? (
                    <WidgetGrid widgets={dashboard.widgets} />
                ) : (
                    <EmptyState
                        title={t('dashboard.empty_title')}
                        description={t('dashboard.empty_text')}
                        action={
                            <div className="flex flex-wrap justify-center gap-2">
                                {menu
                                    .flatMap((group) => group.items)
                                    .filter((item) => item.slug !== 'dashboard')
                                    .slice(0, 6)
                                    .map((item) => (
                                        <Button
                                            key={item.slug}
                                            asChild
                                            variant="outline"
                                        >
                                            <Link href={item.href}>
                                                <Icon name={item.icon} />
                                                {item.label}
                                            </Link>
                                        </Button>
                                    ))}
                            </div>
                        }
                    />
                )}
            </div>
        </>
    );
}
