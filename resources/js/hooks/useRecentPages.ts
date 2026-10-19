import { useEffect } from 'react';

export type RecentPage = { title: string; url: string };

const KEY = 'admin_recent';
const LIMIT = 5;

/** localStorage can throw (private mode, quota); the palette works without it. */
export function readRecentPages(): RecentPage[] {
    try {
        const stored: unknown = JSON.parse(localStorage.getItem(KEY) ?? '[]');

        return Array.isArray(stored)
            ? stored.filter(
                  (entry): entry is RecentPage =>
                      typeof entry?.title === 'string' &&
                      typeof entry?.url === 'string',
              )
            : [];
    } catch {
        return [];
    }
}

/** Records the current admin page (title + URL) as the newest of the last 5. */
export function useRecordRecentPage(page: RecentPage | null): void {
    const title = page?.title;
    const url = page?.url;

    useEffect(() => {
        if (title === undefined || url === undefined) {
            return;
        }

        try {
            const others = readRecentPages().filter(
                (entry) => entry.url !== url,
            );
            localStorage.setItem(
                KEY,
                JSON.stringify([{ title, url }, ...others].slice(0, LIMIT)),
            );
        } catch {
            // Recent pages are a convenience; without storage there are none.
        }
    }, [title, url]);
}
