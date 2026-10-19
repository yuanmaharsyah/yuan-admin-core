<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Registry;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Server side of `{Name}::{Page}` (spec §3.2): each enabled module's
 * `resources/js/pages` becomes a namespace of Inertia's page finder, `App::`
 * points to the project's `resources/js/pages`, and `admin-core.pages.overrides`
 * renames pages before they are rendered.
 */
final readonly class PageResolver
{
    public function __construct(private ModuleRegistry $registry) {}

    public function register(Application $app): void
    {
        $app->bind('inertia.view-finder', fn (Application $app): CaseSensitiveViewFinder => $this->finder($app));

        /** @var array<string, string> $overrides */
        $overrides = config()->array('admin-core.pages.overrides');

        Inertia::transformComponentUsing(
            fn (mixed $component): mixed => is_string($component) ? ($overrides[$component] ?? $component) : $component,
        );
    }

    public function finder(Application $app): CaseSensitiveViewFinder
    {
        /** @var list<string> $paths */
        $paths = config()->array('inertia.pages.paths');
        /** @var list<string> $extensions */
        $extensions = config()->array('inertia.pages.extensions');

        $finder = new CaseSensitiveViewFinder($app->make(Filesystem::class), $paths, $extensions);
        $finder->addNamespace('App', $app->resourcePath('js/pages'));

        foreach ($this->registry->enabled() as $module) {
            $finder->addNamespace($module->name, $module->path('resources/js/pages'));
        }

        return $finder;
    }

    /**
     * The page's file relative to the project root, for `@vite` preloading
     * in the root Blade view.
     */
    public function viteEntry(string $component): string
    {
        return ltrim(Str::after($this->finder(app())->find($component), base_path()), '/');
    }
}
