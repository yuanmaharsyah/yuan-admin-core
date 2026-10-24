import type { id } from './id';

/* English fallback; tsc fails on a missing or an extra key. */
export const en: Record<keyof typeof id, string> = {
    'dashboard.title': 'Home',
    'dashboard.morning': 'Good morning, {name}',
    'dashboard.noon': 'Good afternoon, {name}',
    'dashboard.afternoon': 'Good afternoon, {name}',
    'dashboard.evening': 'Good evening, {name}',
    'dashboard.range': 'Range',
    'dashboard.days': '{days} days',
    'dashboard.empty_title': 'No summary for you yet',
    'dashboard.empty_text': 'Open a module you can access:',
};
