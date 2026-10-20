import { router, useForm } from '@inertiajs/react';
import type { FormDataType } from '@inertiajs/core';
import { useState } from 'react';

type Method = 'get' | 'post' | 'put' | 'patch' | 'delete';

/**
 * `useForm` with the admin form behaviour (frontend-behavior §6): on a
 * validation error, focus and scroll to the first invalid field; a 409 opens
 * the StaleUpdateDialog (`stale`, `dirtyData`, `reload`) instead of Inertia's
 * error page. Pair it with `UnsavedChangesGuard when={form.isDirty}`.
 */
export function useAdminForm<T extends FormDataType<T>>(initial: T) {
    const form = useForm<T>(initial);
    const [stale, setStale] = useState(false);

    const dirtyData = Object.fromEntries(
        Object.entries(form.data).filter(
            ([key, value]) =>
                JSON.stringify(value) !==
                JSON.stringify(initial[key as keyof T]),
        ),
    );

    return {
        form,
        stale,
        dirtyData,
        /** Fresh props for the page, discarding the user's changes. */
        reload: () =>
            router.visit(window.location.href, { preserveState: false }),
        submit: (route: { url: string; method: Method }) =>
            form.submit(route.method, route.url, {
                preserveScroll: true,
                onError: () =>
                    window.requestAnimationFrame(() => {
                        const invalid = document.querySelector<HTMLElement>(
                            '[aria-invalid="true"]',
                        );
                        invalid?.focus();
                        invalid?.scrollIntoView({ block: 'center' });
                    }),
                onHttpException: (response) => {
                    if (response.status !== 409) {
                        return;
                    }

                    setStale(true);

                    return false;
                },
            }),
    };
}
