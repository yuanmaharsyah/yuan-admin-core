<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Yuan\AdminCore\PHPStan\RequestInputRule;

/**
 * @extends RuleTestCase<RequestInputRule>
 */
final class RequestInputRuleTest extends RuleTestCase
{
    public function test_it_reports_raw_request_input_in_controllers(): void
    {
        $this->analyse([__DIR__.'/Fixtures/Http/Controllers/InputController.php'], [
            ['Request all() reads raw input; take a laravel-data object as the action\'s parameter instead (spec §9.1).', 27],
            ['Request input() reads raw input; take a laravel-data object as the action\'s parameter instead (spec §9.1).', 28],
            ['Request validated() reads raw input; take a laravel-data object as the action\'s parameter instead (spec §9.1).', 29],
            ['Request all() reads raw input; take a laravel-data object as the action\'s parameter instead (spec §9.1).', 30],
        ]);
    }

    public function test_it_ignores_code_outside_controllers(): void
    {
        $this->analyse([__DIR__.'/Fixtures/Services/OutsideController.php'], []);
    }

    protected function getRule(): Rule
    {
        return new RequestInputRule;
    }
}
