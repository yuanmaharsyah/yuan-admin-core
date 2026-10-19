<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Testing;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Inputs for the Pest arch tests of spec §9.1 that Pest can't express alone.
 * Works from the filesystem, since arch tests run without an application.
 */
final class ArchRules
{
    /**
     * The namespace of every module folder in the given roots.
     *
     * @param  list<array{path: string, namespace: string}>  $roots
     * @return array<string, string> module namespace (no trailing `\`) => folder
     */
    public static function modules(array $roots): array
    {
        $modules = [];

        foreach ($roots as $root) {
            foreach (glob($root['path'].'/*/module.php') ?: [] as $file) {
                $modules[rtrim($root['namespace'], '\\').'\\'.basename(dirname($file))] = dirname($file);
            }
        }

        return $modules;
    }

    /**
     * For each module, the namespaces of the other modules it must not use:
     * every top-level folder except `Models` (AGENTS §3).
     *
     * @param  list<array{path: string, namespace: string}>  $roots
     * @return array<string, list<string>>
     */
    public static function isolation(array $roots): array
    {
        $modules = self::modules($roots);
        $forbidden = [];

        foreach ($modules as $namespace => $folder) {
            $forbidden[$namespace] = array_values(array_map(
                fn (string $dir): string => $namespace.'\\'.basename($dir),
                array_filter(glob($folder.'/*', GLOB_ONLYDIR) ?: [], fn (string $dir): bool => preg_match('/^[A-Z]/', basename($dir)) === 1 && basename($dir) !== 'Models'),
            ));
        }

        $rules = [];

        foreach (array_keys($modules) as $namespace) {
            $rules[$namespace] = array_merge([], ...array_values(array_diff_key($forbidden, [$namespace => true])));
        }

        return $rules;
    }

    /**
     * PHP files under the paths whose source matches the pattern, skipping
     * any path containing one of `$skip`.
     *
     * @param  list<string>  $paths
     * @param  list<string>  $skip
     * @return list<string>
     */
    public static function filesMatching(array $paths, string $pattern, array $skip = []): array
    {
        $matches = [];

        foreach (array_filter($paths, is_dir(...)) as $path) {
            /** @var SplFileInfo $file */
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                $name = $file->getPathname();

                if ($file->getExtension() === 'php' && ! array_any($skip, fn (string $part): bool => str_contains($name, $part))
                    && preg_match($pattern, (string) file_get_contents($name)) === 1) {
                    $matches[] = $name;
                }
            }
        }

        return $matches;
    }
}
