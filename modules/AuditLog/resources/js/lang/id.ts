import type { ModuleDictionary } from '@admin-core/lib/i18n';

/* Strings of the AuditLog module; every key starts with `audit-log.`. `en.ts` must have exactly the same keys. */
export const id = {
    'audit-log.title': 'Log Aktivitas',
    'audit-log.description': 'Jejak semua perubahan dan aktivitas keamanan.',
    'audit-log.filters': 'Filter log',
    'audit-log.range_7d': '7 hari',
    'audit-log.range_30d': '30 hari',
    'audit-log.range_90d': '90 hari',
    'audit-log.user': 'User',
    'audit-log.all_users': 'Semua user',
    'audit-log.module': 'Modul',
    'audit-log.all_modules': 'Semua modul',
    'audit-log.total': 'Total Aktivitas',
    'audit-log.active_users': 'User Aktif',
    'audit-log.failed_logins': 'Login Gagal',
    'audit-log.check': 'Perlu dicek',
    'audit-log.stack': 'Unduhan, hak akses, dan login',
    'audit-log.downloads': 'Total Unduhan',
    'audit-log.rows_downloaded': 'Baris Diunduh',
    'audit-log.permission_changes': 'Perubahan Hak Akses',
    'audit-log.logins': 'Login',
    'audit-log.logins_ok': 'Berhasil',
    'audit-log.logins_failed': 'Gagal',
    'audit-log.logins_bars': 'Login berhasil {ok}, gagal {failed}',
    'audit-log.by_type': 'Aktivitas per Jenis',
    'audit-log.by_type_filter': 'Filter event {label}, {value}',
    'audit-log.by_day': 'Aktivitas per Hari',
    'audit-log.by_week': 'Aktivitas per Minggu',
    'audit-log.count': 'Jumlah aktivitas',
    'audit-log.tip_count': '{count} aktivitas',
    'audit-log.tip_week': 'Minggu mulai {date}',
    'audit-log.list_title': 'Riwayat',
    'audit-log.list': 'Riwayat aktivitas',
    'audit-log.item': 'aktivitas',
    'audit-log.search': 'Cari subjek…',
    'audit-log.col_time': 'Waktu',
    'audit-log.col_user': 'User',
    'audit-log.col_module': 'Modul',
    'audit-log.col_event': 'Event',
    'audit-log.col_subject': 'Subjek',
    'audit-log.detail': 'Lihat detail',
    'audit-log.empty_title': 'Tidak ada aktivitas pada rentang ini',
    'audit-log.empty_text':
        'Ubah rentang tanggal atau filter untuk melihat aktivitas lain.',
} as const satisfies ModuleDictionary<'audit-log'>;

declare module '@admin-core/lib/i18n' {
    interface Dictionaries {
        'audit-log': typeof id;
    }
}
