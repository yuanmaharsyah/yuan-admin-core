import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';

type State = Yuan.AdminCore.Data.DataTableStateData;

export type TableQuery = {
    search?: string | null;
    sort?: string | null;
    filter?: Record<string, string>;
    per_page?: number;
    page?: number;
};

const DEFAULT_PER_PAGE = 25;

/**
 * The list's URL state (frontend-behavior §5). Every change is an Inertia GET
 * of the current path with `preserveState`, `preserveScroll` and `replace`;
 * search is debounced 300ms; a search or filter change goes back to page 1.
 * `loading` drives the skeleton rows, `failed` the inline error. `only`
 * limits each visit to the list's props (a partial reload).
 */
export function useTableQuery(state: State, page: number, only?: string[]) {
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const [search, setSearch] = useState(state.search ?? '');
    const timer = useRef<number | undefined>(undefined);

    const current: TableQuery = {
        search: state.search,
        sort: state.sort,
        filter: state.filter,
        per_page: state.per_page,
        page,
    };

    const visit = (changes: TableQuery) => {
        window.clearTimeout(timer.current);
        const next = { ...current, ...changes };

        router.get(
            window.location.pathname,
            { ...state.params, ...toParams(next) },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only,
                onStart: () => {
                    setLoading(true);
                    setFailed(false);
                },
                onFinish: () => setLoading(false),
                onHttpException: () => {
                    setFailed(true);

                    return false;
                },
                onNetworkError: () => {
                    setFailed(true);

                    return false;
                },
            },
        );
    };

    const filtered =
        Boolean(state.search) || Object.keys(state.filter).length > 0;

    return {
        loading,
        failed,
        filtered,
        search,
        setSearch: (value: string) => {
            setSearch(value);
            window.clearTimeout(timer.current);
            timer.current = window.setTimeout(
                () => visit({ search: value, page: 1 }),
                300,
            );
        },
        setFilter: (key: string, value: string | null) => {
            const filter = { ...state.filter };

            if (value === null) {
                delete filter[key];
            } else {
                filter[key] = value;
            }

            visit({ filter, page: 1 });
        },
        reset: () => {
            setSearch('');
            visit({ search: null, filter: {}, page: 1 });
        },
        /** asc → desc → none. */
        toggleSort: (column: string) =>
            visit({
                sort:
                    state.sort === column
                        ? `-${column}`
                        : state.sort === `-${column}`
                          ? null
                          : column,
            }),
        setPage: (next: number) => visit({ page: next }),
        setPerPage: (perPage: number) => visit({ per_page: perPage, page: 1 }),
        retry: () => visit({}),
        /**
         * The list's download with the current search, filters and sort: the
         * server answers with the file (the page stays) or a "queued" flash.
         */
        download: (href: string, onFinish?: () => void) =>
            router.get(
                href,
                {
                    ...state.params,
                    ...toParams({
                        search: state.search,
                        sort: state.sort,
                        filter: state.filter,
                    }),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onFinish,
                },
            ),
    };
}

/** Only non-default values go into the URL, so a plain list keeps a clean URL. */
function toParams(
    query: TableQuery,
): Record<string, string | number | Record<string, string>> {
    const params: Record<string, string | number | Record<string, string>> = {};

    if (query.search) {
        params.search = query.search;
    }

    if (query.sort) {
        params.sort = query.sort;
    }

    if (query.filter && Object.keys(query.filter).length > 0) {
        params.filter = query.filter;
    }

    if (query.per_page && query.per_page !== DEFAULT_PER_PAGE) {
        params.per_page = query.per_page;
    }

    if (query.page && query.page > 1) {
        params.page = query.page;
    }

    return params;
}
