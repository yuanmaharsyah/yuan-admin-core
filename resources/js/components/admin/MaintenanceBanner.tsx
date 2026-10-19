import { TriangleAlert } from 'lucide-react';

/** Full-width notice under the topbar while `app.maintenance_banner` is set. */
export function MaintenanceBanner({ message }: { message: string | null }) {
    if (!message) {
        return null;
    }

    return (
        <div
            role="status"
            className="flex shrink-0 items-center gap-2.5 bg-warning/12 px-4 py-2.5 text-table lg:px-8"
        >
            <TriangleAlert
                aria-hidden="true"
                className="size-4 shrink-0 text-warning"
            />
            <span>{message}</span>
        </div>
    );
}
