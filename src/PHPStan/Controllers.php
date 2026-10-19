<?php

declare(strict_types=1);

namespace Yuan\AdminCore\PHPStan;

use PHPStan\Analyser\Scope;

/**
 * The scope of the controller rules: any class under an `Http\Controllers`
 * namespace (project `App`, module roots, core).
 */
final class Controllers
{
    public static function contains(Scope $scope): bool
    {
        return str_contains($scope->getClassReflection()?->getName() ?? '', '\\Http\\Controllers\\');
    }
}
