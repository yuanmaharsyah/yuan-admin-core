<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use Composer\Semver\Semver;
use Illuminate\Console\Command;
use Yuan\AdminCore\Registry\ModuleRegistry;

final class DoctorCommand extends Command
{
    protected $signature = 'admin:doctor';

    protected $description = 'Check npm dependencies, the module cache and environment settings';

    private int $failures = 0;

    public function handle(ModuleRegistry $registry): int
    {
        $this->checkNpmDependencies();
        $this->checkModuleCache($registry);
        $this->checkEnvironment();

        return $this->failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function checkNpmDependencies(): void
    {
        foreach ($this->requiredNpmPackages() as $package => $range) {
            $manifest = $this->laravel->basePath("node_modules/{$package}/package.json");
            $installed = is_file($manifest) ? json_decode((string) file_get_contents($manifest), true) : null;
            $version = is_array($installed) && is_string($installed['version'] ?? null) ? $installed['version'] : null;

            match (true) {
                $version === null => $this->failed("npm {$package}", "not installed (needs {$range})"),
                ! Semver::satisfies($version, $range) => $this->failed("npm {$package}", "{$version} does not satisfy {$range}"),
                default => $this->passed("npm {$package}", $version),
            };
        }
    }

    private function checkModuleCache(ModuleRegistry $registry): void
    {
        match (true) {
            ! $registry->isCached() => $this->passed('Module cache', 'not cached'),
            $registry->cachedEntries() != $registry->scan() => $this->failed('Module cache', 'stale: run php artisan modules:cache'),
            default => $this->passed('Module cache', 'up to date'),
        };
    }

    private function checkEnvironment(): void
    {
        $config = config();
        $production = $this->laravel->environment('production');

        $config->get('app.key')
            ? $this->passed('APP_KEY', 'set')
            : $this->failed('APP_KEY', 'empty: run php artisan key:generate');

        $production && $config->get('app.debug')
            ? $this->failed('APP_DEBUG', 'must be false in production')
            : $this->passed('APP_DEBUG', $config->get('app.debug') ? 'true' : 'false');

        $production && $config->get('queue.default') === 'sync'
            ? $this->failed('QUEUE_CONNECTION', 'must not be sync in production')
            : $this->passed('QUEUE_CONNECTION', $config->string('queue.default'));

        $this->passed('APP_TIMEZONE', $config->string('app.timezone'));
    }

    /**
     * @return array<string, string>
     */
    private function requiredNpmPackages(): array
    {
        $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/admin-core.npm.json'), true);
        $dependencies = is_array($manifest) ? ($manifest['dependencies'] ?? []) : [];

        return is_array($dependencies) ? array_filter($dependencies, 'is_string') : [];
    }

    private function passed(string $check, string $detail): void
    {
        $this->components->twoColumnDetail($check, "<fg=green>OK</> {$detail}");
    }

    private function failed(string $check, string $detail): void
    {
        $this->failures++;
        $this->components->twoColumnDetail($check, "<fg=red>FAIL</> {$detail}");
    }
}
