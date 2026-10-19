import type { ReactNode } from 'react';
import { usePermission } from '../../hooks/usePermission';

type Props = { children: ReactNode } & (
    { permission: string; any?: never } | { any: string[]; permission?: never }
);

/** Renders its children only with the permission (or any of them); hides otherwise. */
export function Can({ children, permission, any }: Props) {
    const { can, canAny } = usePermission();
    const allowed = permission !== undefined ? can(permission) : canAny(any);

    return allowed ? children : null;
}
