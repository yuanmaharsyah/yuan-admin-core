/**
 * Resolves an Inertia page name (spec §3.2):
 * - `Name::Page` → `modules/Name/resources/js/pages/Page.tsx` in the core or project root
 * - `App::Page` → the project's `resources/js/pages/Page.tsx`
 * - `Core::Page` → the package's `resources/js/pages/Page.tsx` (auth, errors)
 * - any other name → the project's `resources/js/pages/{name}.tsx`
 *
 * `pages` is an `import.meta.glob` made from `resources/js/app.tsx`; the
 * page's default export is returned.
 */
export function resolvePage<T>(
    name: string,
    pages: Record<string, () => Promise<{ default: T }>>,
): Promise<T> {
    const [namespace, page] = name.includes('::')
        ? name.split('::', 2)
        : ['App', name];
    const suffix =
        namespace === 'Core'
            ? `/vendor/yuanmaharsyah/admin-core/resources/js/pages/${page}.tsx`
            : `/modules/${namespace}/resources/js/pages/${page}.tsx`;
    const key =
        namespace === 'App'
            ? `./pages/${page}.tsx`
            : Object.keys(pages).find((path) => path.endsWith(suffix));
    const load = key === undefined ? undefined : pages[key];

    if (load === undefined) {
        throw new Error(`Inertia page not found: ${name}`);
    }

    return load().then((module) => module.default);
}
