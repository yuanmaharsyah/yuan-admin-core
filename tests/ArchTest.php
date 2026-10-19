<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Yuan\AdminCore\Testing\ArchRules;

// The arch rows of spec §9.1, for core code, core modules and the compliant fixture modules.
// Each expectation names one namespace: Pest silently passes `expect([$a, $b])->not->toUse()`
// and `->not->toBeUsedIn()` with a list (found in the 04b rehearsal).

$roots = [
    ['path' => dirname(__DIR__).'/modules', 'namespace' => 'Yuan\\AdminCore\\Modules\\'],
    ['path' => __DIR__.'/Fixtures/Contract', 'namespace' => 'Yuan\\AdminCore\\Tests\\Fixtures\\Contract\\'],
];
$modules = array_keys(ArchRules::modules($roots));
$targets = ['Yuan\AdminCore', ...$modules];
$production = [dirname(__DIR__).'/src', ...array_values(ArchRules::modules($roots))];

arch('code declares strict types', function () use ($targets): void {
    foreach ($targets as $target) {
        expect($target)->toUseStrictTypes();
    }
});

arch('no debugging helpers and no env()', function () use ($targets): void {
    foreach ($targets as $target) {
        expect($target)->not->toUse(['dd', 'dump', 'ray', 'var_dump', 'env']);
    }
});

it('never unguards a model', function () use ($production): void {
    expect(ArchRules::filesMatching($production, '/\$guarded\s*=\s*\[\s*\]/'))->toBe([]);
});

arch('modules use another module only through its Models', function () use ($roots, $modules): void {
    $rules = ArchRules::isolation($roots);
    expect(array_keys($rules))->toBe($modules);

    foreach ($rules as $module => $forbidden) {
        expect($module)->not->toUse($forbidden);
    }
});

arch('controllers use neither the DB facade nor FormRequests', function () use ($modules): void {
    foreach ($modules as $module) {
        expect("{$module}\\Http\\Controllers")->not->toUse([DB::class, FormRequest::class]);
    }
});

arch('Data classes are laravel-data objects with #[TypeScript]', function () use ($modules): void {
    foreach (['Yuan\AdminCore\Data', ...array_map(fn (string $module): string => "{$module}\\Data", $modules)] as $namespace) {
        expect($namespace)->classes()->toExtend(Data::class)->toHaveAttribute(TypeScript::class);
    }
});

arch('no FormRequests: input is a laravel-data object', function () use ($targets): void {
    foreach ($targets as $target) {
        expect($target)->classes()->not->toExtend(FormRequest::class);
    }
});

// A source scan: Pest can't see a class that isn't installed (maatwebsite/excel arrives in Phase 06).
it('downloads only through ExportManager', function () use ($production): void {
    expect(ArchRules::filesMatching($production, '/Excel::download|Maatwebsite\\\\Excel\\\\Facades\\\\Excel/', ['/src/Exports/ExportManager.php']))->toBe([]);
});

arch('activity log only through Yuan\AdminCore\Audit', function () use ($targets): void {
    foreach ($targets as $target) {
        expect($target)->not->toUse(['activity', 'Spatie\Activitylog'])->ignoring('Yuan\AdminCore\Audit');
    }
});

it('names the activity_log table only in the audit code', function () use ($production): void {
    expect(ArchRules::filesMatching($production, '/[\'"]activity_log[\'"]/', ['/src/Audit/', '/Tests/']))->toBe([]);
});
