/**
 * A JSON request with Laravel's CSRF cookie, for the few endpoints that answer
 * JSON instead of an Inertia page (Fortify's two-step and password-confirm
 * routes). Everything else goes through Inertia's router.
 */
export type JsonResult<T> = {
    ok: boolean;
    status: number;
    data: T | null;
    /** Validation messages (422), field => first message. */
    errors: Record<string, string>;
};

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export async function sendJson<T = unknown>(
    url: string,
    method: 'GET' | 'POST' | 'DELETE' = 'GET',
    body?: Record<string, string>,
): Promise<JsonResult<T>> {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });
    const text = await response.text();
    const parsed: unknown = text === '' ? null : JSON.parse(text);
    const errors: Record<string, string> = {};

    if (
        response.status === 422 &&
        typeof parsed === 'object' &&
        parsed !== null &&
        'errors' in parsed &&
        typeof parsed.errors === 'object' &&
        parsed.errors !== null
    ) {
        for (const [field, messages] of Object.entries(parsed.errors)) {
            if (Array.isArray(messages) && typeof messages[0] === 'string') {
                errors[field] = messages[0];
            }
        }
    }

    return {
        ok: response.ok,
        status: response.status,
        data: response.ok ? (parsed as T) : null,
        errors,
    };
}
