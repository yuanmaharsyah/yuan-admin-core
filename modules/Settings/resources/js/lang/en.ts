import type { id } from './id';

/* English fallback; tsc fails on a missing or an extra key. */
export const en: Record<keyof typeof id, string> = {
    'settings.title': 'Settings',
    'settings.description':
        'Application settings. Only a Super Admin can change them.',
    'settings.tabs': 'Settings sections',
};
