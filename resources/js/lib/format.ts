/*
 * The only formatters for user-facing values (frontend-behavior §2): locale
 * id-ID, dates in the app timezone (`app.timezone`, passed by the caller).
 * Examples: 16 Okt 2026 · 16 Okt 2026 13.40 · Rp 1.250.000 · 1.250,5
 */
const LOCALE = 'id-ID';

type DateInput = string | number | Date;

export function formatDate(value: DateInput, timeZone: string): string {
    return new Intl.DateTimeFormat(LOCALE, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone,
    }).format(new Date(value));
}

export function formatDateTime(value: DateInput, timeZone: string): string {
    return new Intl.DateTimeFormat(LOCALE, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone,
    }).format(new Date(value));
}

export function formatNumber(
    value: number,
    maximumFractionDigits = 2,
    useGrouping = true,
): string {
    return new Intl.NumberFormat(LOCALE, {
        maximumFractionDigits,
        useGrouping,
    }).format(value);
}

export function formatCurrency(value: number): string {
    return new Intl.NumberFormat(LOCALE, {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

/*
 * Form values travel without a timezone, already in the app timezone: a date
 * as `YYYY-MM-DD`, a date and time as `YYYY-MM-DD HH:mm` (frontend-behavior §6).
 * They are formatted as UTC so no browser offset shifts them.
 */
export function formatLocalDate(value: string): string {
    return formatDate(`${value.slice(0, 10)}T00:00:00Z`, 'UTC');
}

export function formatLocalDateTime(value: string): string {
    return formatDateTime(
        `${value.slice(0, 10)}T${value.slice(11, 16)}:00Z`,
        'UTC',
    );
}

/**
 * Typed `dd/mm/yyyy` (also `-` or `.`, two-digit years as 20yy) to
 * `YYYY-MM-DD`; null when it isn't a real date.
 */
export function parseLocalDate(text: string): string | null {
    const match = /^(\d{1,2})[/.-](\d{1,2})[/.-](\d{2}|\d{4})$/.exec(
        text.trim(),
    );

    if (!match) {
        return null;
    }

    const [day, month] = [Number(match[1]), Number(match[2])];
    const year =
        match[3].length === 2 ? 2000 + Number(match[3]) : Number(match[3]);
    const date = new Date(Date.UTC(year, month - 1, day));

    if (
        date.getUTCFullYear() !== year ||
        date.getUTCMonth() !== month - 1 ||
        date.getUTCDate() !== day
    ) {
        return null;
    }

    return date.toISOString().slice(0, 10);
}

/**
 * A typed or pasted number in either notation: `1.250.000,5` (id-ID) or
 * `1,250,000.5`. With both separators the last one is the decimal mark; a
 * repeated separator is a thousands mark; a single `,` is decimal. A single
 * `.` depends on the field: in a decimal field (`decimalDot`) it is the
 * decimal mark (`0.125` → 0,125; `12.5` → 12,5); in a whole-number field it
 * is a thousands mark when exactly three digits follow it (`1.250` → 1250).
 * Null when nothing numeric is left. NumberField shows the result in id-ID
 * on blur, so the user sees how the input was read.
 */
export function parseNumber(text: string, decimalDot = false): number | null {
    let value = text.replace(/[^\d,.-]/g, '');
    const comma = value.lastIndexOf(',');
    const dot = value.lastIndexOf('.');

    if (comma >= 0 && dot >= 0) {
        const decimal = comma > dot ? ',' : '.';
        value = value
            .split(decimal === ',' ? '.' : ',')
            .join('')
            .replace(decimal, '.');
    } else if (comma >= 0) {
        value =
            value.indexOf(',') !== comma
                ? value.split(',').join('')
                : value.split('.').join('').replace(',', '.');
    } else if (
        dot >= 0 &&
        (value.indexOf('.') !== dot || (!decimalDot && /\.\d{3}$/.test(value)))
    ) {
        value = value.split('.').join('');
    }

    if (!/^-?\d+(\.\d+)?$/.test(value)) {
        return null;
    }

    return Number(value);
}

/** A file size: `850 B`, `12,4 KB`, `3,2 MB`. */
export function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${formatNumber(bytes, 0)} B`;
    }

    return bytes < 1024 * 1024
        ? `${formatNumber(bytes / 1024, 1)} KB`
        : `${formatNumber(bytes / 1024 / 1024, 1)} MB`;
}
