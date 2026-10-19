import { en } from '../lang/en';
import { id } from '../lang/id';

export type MessageKey = keyof typeof id;
export type Messages = Record<MessageKey, string>;

const dictionaries: Record<string, Messages> = { id, en };

/** `{name}` placeholders are replaced from `replacements`; unknown ones stay. */
export function translate(
    locale: string,
    key: MessageKey,
    replacements: Record<string, string | number> = {},
): string {
    const message = (dictionaries[locale] ?? id)[key];

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
