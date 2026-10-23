import type { id } from './id';

/* English fallback; tsc fails on a missing or an extra key. */
export const en: Record<keyof typeof id, string> = {
    'levels.title': 'Levels',
    'levels.description':
        'A level decides what each user may do. Only a Super Admin manages levels.',
    'levels.list': 'Level list',
    'levels.name': 'Name',
    'levels.desc': 'Description',
    'levels.users': 'Users',
    'levels.protected': 'Protected: cannot be changed',
    'levels.permissions': 'Permissions',
    'levels.create_title': 'New level',
    'levels.edit_title': 'Edit {name}',
    'levels.delete_title': 'Delete level',
    'levels.delete_text':
        'The level is deleted for good. A level that users still have cannot be deleted.',
    'levels.permissions_title': 'Permissions · {name}',
    'levels.permissions_text': 'Tick what this level may do in each module.',
};
