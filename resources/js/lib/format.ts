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

export function formatNumber(value: number, maximumFractionDigits = 2): string {
    return new Intl.NumberFormat(LOCALE, { maximumFractionDigits }).format(
        value,
    );
}

export function formatCurrency(value: number): string {
    return new Intl.NumberFormat(LOCALE, {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}
