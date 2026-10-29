<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use Illuminate\Filesystem\Filesystem;

/**
 * Wayfinder writes each controller's file as `@see {path}`, relative to the
 * project. In the boilerplate repo vendor/yuanmaharsyah/admin-core is a symlink to
 * packages/admin-core, PHP reports the resolved path, and the package's
 * files land as absolute paths (machine-specific, so the generated files
 * differ per checkout). This rewrites them to `vendor/yuanmaharsyah/admin-core/`,
 * which is what a project with the package installed gets anyway.
 */
final class WayfinderPaths
{
    /** Wayfinder's output folders under resources/js. */
    private const array FOLDERS = ['actions', 'routes', 'wayfinder'];

    public static function normalize(string $basePath): int
    {
        $package = realpath("{$basePath}/vendor/yuanmaharsyah/admin-core");

        // Inside the (resolved) project: a real vendor folder, nothing to do.
        if ($package === false || str_starts_with($package, rtrim((string) realpath($basePath), '/').'/')) {
            return 0;
        }

        $from = '@see '.ltrim($package, '/').'/';
        $files = new Filesystem;
        $changed = 0;

        foreach (self::FOLDERS as $folder) {
            if (! is_dir("{$basePath}/resources/js/{$folder}")) {
                continue;
            }

            foreach ($files->allFiles("{$basePath}/resources/js/{$folder}") as $file) {
                $contents = $file->getContents();

                if (str_contains($contents, $from)) {
                    $files->put($file->getPathname(), str_replace($from, '@see vendor/yuanmaharsyah/admin-core/', $contents));
                    $changed++;
                }
            }
        }

        return $changed;
    }
}
