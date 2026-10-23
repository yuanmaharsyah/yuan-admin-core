import type { ModuleDictionary } from '@admin-core/lib/i18n';

/* Strings of the Users module; every key starts with `users.`. `en.ts` must have exactly the same keys. */
export const id = {
    'users.title': 'Users',
    'users.description': 'Kelola akun pengguna dan level aksesnya.',
    'users.list': 'Daftar user',
    'users.item': 'user',
    'users.search': 'Cari nama, username, email…',
    'users.empty_title': 'Belum ada user',
    'users.empty_text':
        'Tambahkan user pertama untuk mulai memberi akses ke aplikasi.',
    'users.create_title': 'Tambah user',
    'users.edit_title': 'Edit {name}',
    'users.delete_title': 'Hapus user',
    'users.delete_text':
        'User akan dipindahkan ke Sampah dan tidak bisa masuk lagi. Super Admin dapat memulihkannya.',
    'users.section_account': 'Akun',
    'users.section_access': 'Akses',
    'users.section_extra': 'Data tambahan',
    'users.name': 'Nama',
    'users.username': 'Username',
    'users.username_help':
        'Huruf, angka, titik, garis bawah atau tanda hubung. Tanpa "@".',
    'users.email': 'Email',
    'users.level': 'Level',
    'users.status': 'Status',
    'users.active': 'Aktif',
    'users.inactive': 'Nonaktif',
    'users.active_switch': 'Akun aktif (dapat masuk)',
    'users.password': 'Kata sandi',
    'users.password_confirmation': 'Konfirmasi kata sandi',
    'users.password_note':
        'User wajib mengganti kata sandi ini saat pertama masuk.',
    'users.last_login': 'Login terakhir',
    'users.never': 'Belum pernah',
    'users.created': 'Dibuat',
    'users.must_change': 'Wajib ganti kata sandi',
    'users.yes': 'Ya',
    'users.no': 'Tidak',
    'users.activate': 'Aktifkan',
    'users.deactivate': 'Nonaktifkan',
    'users.deactivate_title': 'Nonaktifkan user',
    'users.deactivate_text':
        'User tidak bisa masuk, dan semua sesinya langsung berakhir. Anda bisa mengaktifkannya lagi.',
    'users.reset_password': 'Reset kata sandi',
    'users.reset_title': 'Reset kata sandi',
    'users.reset_text':
        'Semua sesi user berakhir, dan user wajib mengganti kata sandi saat masuk berikutnya.',
    'users.reset_generate': 'Buat kata sandi sementara',
    'users.reset_set': 'Tentukan sendiri',
    'users.reset_submit': 'Reset',
    'users.temporary_title': 'Kata sandi sementara',
    'users.temporary_text':
        'Berikan kepada user lewat jalur yang aman. Kata sandi ini hanya ditampilkan sekali.',
    'users.copy': 'Salin',
    'users.copied': 'Disalin',
    'users.done': 'Selesai',
} as const satisfies ModuleDictionary<'users'>;

declare module '@admin-core/lib/i18n' {
    interface Dictionaries {
        users: typeof id;
    }
}
