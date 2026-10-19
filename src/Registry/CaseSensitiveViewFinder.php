<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Registry;

use Illuminate\View\FileViewFinder;
use InvalidArgumentException;

/**
 * Inertia page finder that only matches a file whose path has exactly the case
 * of the page name, so `Demo::index` fails on case-insensitive disks too.
 */
final class CaseSensitiveViewFinder extends FileViewFinder
{
    /**
     * @param  string  $name
     * @param  string|list<string>  $paths
     */
    protected function findInPaths($name, $paths): string
    {
        foreach ((array) $paths as $path) {
            foreach ($this->getPossibleViewFiles($name) as $file) {
                if ($this->files->exists($path.'/'.$file) && $this->matchesCase($path, $file)) {
                    return $path.'/'.$file;
                }
            }
        }

        throw new InvalidArgumentException("View [{$name}] not found.");
    }

    private function matchesCase(string $base, string $file): bool
    {
        $directory = $base;

        foreach (explode('/', $file) as $segment) {
            if (! in_array($segment, scandir($directory) ?: [], true)) {
                return false;
            }

            $directory .= '/'.$segment;
        }

        return true;
    }
}
