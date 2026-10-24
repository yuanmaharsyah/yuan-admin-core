import type { ModuleDictionary } from '@admin-core/lib/i18n';

/* Strings of the Dashboard module; every key starts with `dashboard.`. `en.ts` must have exactly the same keys. */
export const id = {
    'dashboard.title': 'Beranda',
    'dashboard.morning': 'Selamat pagi, {name}',
    'dashboard.noon': 'Selamat siang, {name}',
    'dashboard.afternoon': 'Selamat sore, {name}',
    'dashboard.evening': 'Selamat malam, {name}',
    'dashboard.range': 'Rentang',
    'dashboard.days': '{days} hari',
    'dashboard.empty_title': 'Belum ada ringkasan untuk Anda',
    'dashboard.empty_text': 'Buka modul yang bisa Anda akses:',
} as const satisfies ModuleDictionary<'dashboard'>;

declare module '@admin-core/lib/i18n' {
    interface Dictionaries {
        dashboard: typeof id;
    }
}
