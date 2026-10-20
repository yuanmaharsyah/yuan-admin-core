import { en } from '../lang/en';
import { id } from '../lang/id';

/**
 * Every dictionary's `id` keys, by owner. A module adds its own entry from its
 * `resources/js/lang/id.ts`:
 *
 *     declare module '@admin-core/lib/i18n' {
 *         interface Dictionaries { invoices: typeof id }
 *     }
 */
export interface Dictionaries {
    core: typeof id;
}

export type MessageKey = {
    [Owner in keyof Dictionaries]: keyof Dictionaries[Owner];
}[keyof Dictionaries];

/** The core `en` dictionary's type: exactly the core keys. */
export type Messages = Record<keyof typeof id, string>;

/** A module's `id` dictionary: every key starts with `{slug}.`. */
export type ModuleDictionary<Slug extends string> = Record<
    `${Slug}.${string}`,
    string
>;

const dictionaries: Record<string, Record<string, string>> = {
    id: { ...id },
    en: { ...en },
};

/**
 * Merges module dictionaries into `t()`. The app passes an eager
 * `import.meta.glob` of `modules/*\/resources/js/lang/*.ts`; the file name is
 * the locale.
 */
export function registerMessages(
    files: Record<string, Record<string, Record<string, string>>>,
): void {
    for (const [path, exports] of Object.entries(files)) {
        const locale = path.slice(path.lastIndexOf('/') + 1, -'.ts'.length);

        for (const messages of Object.values(exports)) {
            dictionaries[locale] = { ...dictionaries[locale], ...messages };
        }
    }
}

/** `{name}` placeholders are replaced from `replacements`; unknown ones stay. */
export function translate(
    locale: string,
    key: MessageKey,
    replacements: Record<string, string | number> = {},
): string {
    const message = dictionaries[locale]?.[key] ?? dictionaries.id[key] ?? key;

    return message.replace(/\{(\w+)\}/g, (placeholder, name: string) =>
        name in replacements ? String(replacements[name]) : placeholder,
    );
}

/**
 * The translation helper for components: `const t = useT(); t('common.loading')`.
 * The locale is the root view's `<html lang>` (the app locale), so it also
 * works outside a page, e.g. in the toaster.
 */
export function useT() {
    const locale = document.documentElement.lang.split('-')[0] ?? 'id';

    return (key: MessageKey, replacements?: Record<string, string | number>) =>
        translate(locale, key, replacements);
}
