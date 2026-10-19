import type { LucideProps } from 'lucide-react';
import { DynamicIcon } from 'lucide-react/dynamic';
import type { IconName } from 'lucide-react/dynamic';

type Props = Omit<LucideProps, 'ref'> & { name: string };

/**
 * A lucide icon by name, for icons that arrive as data (menu, modules). The
 * server only sends names from resources/icons/lucide.json, which is generated
 * from this lucide-react, so the cast is checked there.
 */
export function Icon({ name, ...props }: Props) {
    return <DynamicIcon name={name as IconName} {...props} />;
}
