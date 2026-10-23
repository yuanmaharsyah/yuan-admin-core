import type { ModuleDictionary } from '@admin-core/lib/i18n';

/* Strings of the Levels module; every key starts with `levels.`. `en.ts` must have exactly the same keys. */
export const id = {
    'levels.title': 'Levels',
    'levels.description':
        'Level menentukan apa yang boleh dilakukan setiap user. Hanya Super Admin yang mengelolanya.',
    'levels.list': 'Daftar level',
    'levels.name': 'Nama',
    'levels.desc': 'Deskripsi',
    'levels.users': 'Jumlah user',
    'levels.protected': 'Dilindungi: tidak dapat diubah',
    'levels.permissions': 'Hak akses',
    'levels.create_title': 'Tambah level',
    'levels.edit_title': 'Edit {name}',
    'levels.delete_title': 'Hapus level',
    'levels.delete_text':
        'Level dihapus permanen. Level yang masih dipakai user tidak dapat dihapus.',
    'levels.permissions_title': 'Hak Akses · {name}',
    'levels.permissions_text':
        'Centang aksi yang boleh dilakukan level ini di setiap modul.',
} as const satisfies ModuleDictionary<'levels'>;

declare module '@admin-core/lib/i18n' {
    interface Dictionaries {
        levels: typeof id;
    }
}
