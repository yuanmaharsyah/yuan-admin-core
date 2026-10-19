<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Yuan\AdminCore\PHPStan\InertiaPropsRule;

/**
 * @extends RuleTestCase<InertiaPropsRule>
 */
final class InertiaPropsRuleTest extends RuleTestCase
{
    public function test_it_reports_arrays_models_and_collections_passed_to_inertia(): void
    {
        $this->analyse([__DIR__.'/Fixtures/Http/Controllers/PropsController.php'], [
            ['Inertia prop \'level\' is Yuan\AdminCore\Permissions\Level; pass a laravel-data object instead (spec §9.1).', 41],
            ['Inertia prop \'rows\' is list<array<string, int>>; pass a laravel-data object instead (spec §9.1).', 41],
            ['Inertia prop \'names\' is Illuminate\Support\Collection<int, string>; pass a laravel-data object instead (spec §9.1).', 41],
            ['Inertia prop \'level\' is Yuan\AdminCore\Permissions\Level; pass a laravel-data object instead (spec §9.1).', 50],
            ['Inertia prop \'level\' is Yuan\AdminCore\Permissions\Level; pass a laravel-data object instead (spec §9.1).', 57],
            ['Inertia::defer() value is Closure; pass a laravel-data object instead (spec §9.1).', 63],
            ['Inertia prop \'status\' is mixed; pass a laravel-data object instead (spec §9.1).', 69],
        ]);
    }

    public function test_it_ignores_code_outside_controllers(): void
    {
        $this->analyse([__DIR__.'/Fixtures/Services/OutsideController.php'], []);
    }

    protected function getRule(): Rule
    {
        return new InertiaPropsRule;
    }
}
