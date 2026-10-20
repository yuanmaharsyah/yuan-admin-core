import { AlertCircle } from 'lucide-react';
import { useT } from '../../lib/i18n';

/**
 * The list of errors at the top of a long form (6+ fields, frontend-behavior
 * §6); each message links to its field.
 */
export function FormErrorSummary({
    errors,
}: {
    errors: Partial<Record<string, string>>;
}) {
    const t = useT();
    const entries = Object.entries(errors).filter(
        (entry): entry is [string, string] => Boolean(entry[1]),
    );

    if (entries.length === 0) {
        return null;
    }

    return (
        <div
            role="alert"
            className="flex items-start gap-2.5 rounded bg-destructive/12 p-3 text-table"
        >
            <AlertCircle
                aria-hidden="true"
                className="mt-0.5 size-4 shrink-0 text-destructive"
            />
            <div className="flex flex-col gap-1">
                <strong className="font-semibold text-destructive">
                    {t('form.errors_title', { count: entries.length })}
                </strong>
                <ul className="m-0 flex list-disc flex-col gap-0.5 pl-4">
                    {entries.map(([name, message]) => (
                        <li key={name}>
                            <a
                                href={`#${name}`}
                                className="underline underline-offset-3"
                                onClick={(event) => {
                                    event.preventDefault();
                                    document.getElementById(name)?.focus();
                                }}
                            >
                                {message}
                            </a>
                        </li>
                    ))}
                </ul>
            </div>
        </div>
    );
}
