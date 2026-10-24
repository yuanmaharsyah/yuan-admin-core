import type { ModuleDictionary } from '@admin-core/lib/i18n';

/* Strings of the Settings module; every key starts with `settings.`. `en.ts` must have exactly the same keys. */
export const id = {
    'settings.title': 'Pengaturan',
    'settings.description':
        'Pengaturan aplikasi. Hanya Super Admin yang dapat mengubahnya.',
    'settings.tabs': 'Bagian pengaturan',
} as const satisfies ModuleDictionary<'settings'>;

declare module '@admin-core/lib/i18n' {
    interface Dictionaries {
        settings: typeof id;
    }
}
