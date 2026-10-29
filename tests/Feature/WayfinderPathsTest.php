<?php

declare(strict_types=1);

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Filesystem\Filesystem;
use Yuan\AdminCore\Console\WayfinderPaths;

// Generated Wayfinder files name the package as vendor/yuanmaharsyah/admin-core/…,
// whether it is installed in vendor/ or symlinked there (the boilerplate repo).

/**
 * A project folder with one generated action file; the package is a real
 * folder in vendor/ or a symlink to one outside the project.
 *
 * @return array{base: string, package: string}
 */
function wayfinderProject(bool $symlinked): array
{
    $root = sys_get_temp_dir().'/wayfinder-'.bin2hex(random_bytes(6));
    $base = "{$root}/project";
    $files = new Filesystem;
    $files->ensureDirectoryExists("{$base}/vendor/yuanmaharsyah");
    $files->ensureDirectoryExists("{$base}/resources/js/actions/Yuan");

    if ($symlinked) {
        $files->ensureDirectoryExists("{$root}/packages/admin-core");
        symlink("{$root}/packages/admin-core", "{$base}/vendor/yuanmaharsyah/admin-core");
    } else {
        $files->ensureDirectoryExists("{$base}/vendor/yuanmaharsyah/admin-core");
    }

    $package = (string) realpath("{$base}/vendor/yuanmaharsyah/admin-core");
    $files->put("{$base}/resources/js/actions/Yuan/HomeController.ts", '/** @see '.ltrim($package, '/')."/src/Http/HomeController.php:20 */\n");

    register_shutdown_function(fn () => $files->deleteDirectory($root));

    return ['base' => $base, 'package' => $package];
}

it('rewrites the resolved package path of a symlinked package', function (): void {
    ['base' => $base] = wayfinderProject(symlinked: true);

    expect(WayfinderPaths::normalize($base))->toBe(1)
        ->and(file_get_contents("{$base}/resources/js/actions/Yuan/HomeController.ts"))
        ->toBe("/** @see vendor/yuanmaharsyah/admin-core/src/Http/HomeController.php:20 */\n");
});

it('leaves a package installed in vendor alone', function (): void {
    ['base' => $base, 'package' => $package] = wayfinderProject(symlinked: false);
    $before = file_get_contents("{$base}/resources/js/actions/Yuan/HomeController.ts");

    expect(WayfinderPaths::normalize($base))->toBe(0)
        ->and(file_get_contents("{$base}/resources/js/actions/Yuan/HomeController.ts"))->toBe($before)
        ->and($package)->toEndWith('/project/vendor/yuanmaharsyah/admin-core');
});

it('runs after every wayfinder:generate', function (): void {
    expect(app('events')->hasListeners(CommandFinished::class))->toBeTrue();
});
