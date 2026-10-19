<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Yuan\AdminCore\Registry\ModuleRegistry;

/**
 * Point the app at a temp base path whose node_modules holds the given versions.
 *
 * @param  array<string, string>  $versions
 */
function nodeModules(array $versions): void
{
    $base = temporaryRoot();

    foreach ($versions as $package => $version) {
        mkdir("{$base}/node_modules/{$package}", recursive: true);
        file_put_contents("{$base}/node_modules/{$package}/package.json", json_encode(['version' => $version]));
    }

    app()->setBasePath($base);
}

const HEALTHY = ['@inertiajs/react' => '3.9.1', 'react' => '19.3.1', 'react-dom' => '19.3.1', 'tailwindcss' => '4.3.3'];

it('passes on a healthy setup', function (): void {
    nodeModules(HEALTHY);

    expect(Artisan::call('admin:doctor'))->toBe(0);
});

it('fails on a missing or outdated npm package', function (): void {
    nodeModules([...HEALTHY, 'react' => '18.3.1', 'tailwindcss' => '']);
    unlink(base_path('node_modules/tailwindcss/package.json'));

    expect(Artisan::call('admin:doctor'))->toBe(1)
        ->and(Artisan::output())
        ->toContain('18.3.1 does not satisfy ^19.2.0')
        ->toContain('not installed (needs ^4.0.0)');
});

it('fails on a stale module cache', function (): void {
    nodeModules(HEALTHY);
    file_put_contents(config()->string('admin-core.modules.cache_path'), '<?php return [];');

    expect(app(ModuleRegistry::class)->isCached())->toBeTrue();

    expect(Artisan::call('admin:doctor'))->toBe(1)
        ->and(Artisan::output())->toContain('stale: run php artisan modules:cache');
});

it('fails on an empty APP_KEY', function (): void {
    nodeModules(HEALTHY);
    config(['app.key' => '']);

    expect(Artisan::call('admin:doctor'))->toBe(1)
        ->and(Artisan::output())->toContain('empty: run php artisan key:generate');
});

it('fails on debug mode or a sync queue in production', function (): void {
    nodeModules(HEALTHY);
    app()['env'] = 'production';
    config(['app.debug' => true, 'queue.default' => 'sync']);

    expect(Artisan::call('admin:doctor'))->toBe(1)
        ->and(Artisan::output())
        ->toContain('must be false in production')
        ->toContain('must not be sync in production');
});
