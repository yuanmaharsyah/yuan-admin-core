<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Registry\ModuleRegistry;
use Yuan\AdminCore\Registry\PageResolver;
use Yuan\AdminCore\Tests\TestCase;

use function Pest\Laravel\actingAs;

it('resolves Name::Page to the module pages folder', function (): void {
    expect(app('inertia.view-finder')->find('Demo::Index'))
        ->toBe(TestCase::FIXTURES.'/Modules/Demo/resources/js/pages/Index.tsx');
});

it('rejects a page name whose case differs from the file on disk', function (string $name): void {
    app('inertia.view-finder')->find($name);
})->with(['Demo::index', 'Demo::INDEX'])->throws(InvalidArgumentException::class);

it('gives the page file relative to the project root for Vite', function (): void {
    app()->setBasePath(dirname(TestCase::FIXTURES));

    expect(app(PageResolver::class)->viteEntry('Demo::Index'))->toBe('Fixtures/Modules/Demo/resources/js/pages/Index.tsx');
});

it('resolves Core::Page to the package pages folder, case-exact and overridable', function (): void {
    expect(app('inertia.view-finder')->find('Core::Errors/Probe'))->toBe(TestCase::FIXTURES.'/CorePages/Errors/Probe.tsx')
        ->and(fn () => app('inertia.view-finder')->find('Core::errors/Probe'))->toThrow(InvalidArgumentException::class);

    app()->setBasePath(dirname(TestCase::FIXTURES));
    expect(app(PageResolver::class)->viteEntry('Core::Errors/Probe'))->toBe('Fixtures/CorePages/Errors/Probe.tsx');
});

it('renders a module page that exists', function (): void {
    AssertableInertia::fromTestResponse(actingAs(new GenericUser(['id' => 1]))->get('/admin/demo'))
        ->component('Demo::Index');
});

it('applies the page override map', function (): void {
    config(['admin-core.pages.overrides' => ['Demo::Index' => 'Notes::Index']]);
    (new PageResolver(app(ModuleRegistry::class)))->register(app());

    AssertableInertia::fromTestResponse(actingAs(new GenericUser(['id' => 1]))->get('/admin/demo'))
        ->component('Notes::Index');
});
