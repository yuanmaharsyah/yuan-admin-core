import { usePage } from '@inertiajs/react';

/**
 * Cosmetic permission checks from `auth` (frontend-behavior §2): gated UI is
 * hidden, and the backend always re-checks. Super Admin passes every check.
 */
export function usePermission() {
    const { auth } = usePage().props;
    const can = (permission: string): boolean =>
        auth.is_super_admin || auth.permissions.includes(permission);

    return {
        can,
        canAny: (permissions: string[]): boolean => permissions.some(can),
        isSuperAdmin: auth.is_super_admin,
    };
}
