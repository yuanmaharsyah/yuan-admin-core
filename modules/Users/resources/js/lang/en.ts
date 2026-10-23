import type { id } from './id';

/* English fallback; tsc fails on a missing or an extra key. */
export const en: Record<keyof typeof id, string> = {
    'users.title': 'Users',
    'users.description': 'Manage user accounts and their access levels.',
    'users.list': 'User list',
    'users.item': 'users',
    'users.search': 'Search name, username, email…',
    'users.empty_title': 'No users yet',
    'users.empty_text':
        'Add the first user to start giving access to the application.',
    'users.create_title': 'New user',
    'users.edit_title': 'Edit {name}',
    'users.delete_title': 'Delete user',
    'users.delete_text':
        'The user moves to the Trash and can no longer sign in. A Super Admin can restore them.',
    'users.section_account': 'Account',
    'users.section_access': 'Access',
    'users.section_extra': 'More details',
    'users.name': 'Name',
    'users.username': 'Username',
    'users.username_help':
        'Letters, digits, dots, underscores or hyphens. No "@".',
    'users.email': 'Email',
    'users.level': 'Level',
    'users.status': 'Status',
    'users.active': 'Active',
    'users.inactive': 'Inactive',
    'users.active_switch': 'Active account (can sign in)',
    'users.password': 'Password',
    'users.password_confirmation': 'Confirm password',
    'users.password_note':
        'The user must change this password at the first sign-in.',
    'users.last_login': 'Last sign-in',
    'users.never': 'Never',
    'users.created': 'Created',
    'users.must_change': 'Must change password',
    'users.yes': 'Yes',
    'users.no': 'No',
    'users.activate': 'Activate',
    'users.deactivate': 'Deactivate',
    'users.deactivate_title': 'Deactivate user',
    'users.deactivate_text':
        'The user can no longer sign in, and all their sessions end at once. You can activate them again.',
    'users.reset_password': 'Reset password',
    'users.reset_title': 'Reset password',
    'users.reset_text':
        "All the user's sessions end, and the user must change the password at the next sign-in.",
    'users.reset_generate': 'Create a temporary password',
    'users.reset_set': 'Set it myself',
    'users.reset_submit': 'Reset',
    'users.temporary_title': 'Temporary password',
    'users.temporary_text':
        'Give it to the user through a safe channel. It is shown only once.',
    'users.copy': 'Copy',
    'users.copied': 'Copied',
    'users.done': 'Done',
};
