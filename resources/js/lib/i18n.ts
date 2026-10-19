import { usePage } from '@inertiajs/react';
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

/** The translation helper for components: `const t = useT(); t('common.loading')`. */
export function useT() {
    const { locale } = usePage().props.app;

    return (key: MessageKey, replacements?: Record<string, string | number>) =>
        translate(locale, key, replacements);
}
