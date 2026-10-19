<?php

declare(strict_types=1);

namespace Yuan\AdminCore\PHPStan;

use Inertia\AlwaysProp;
use Inertia\DeferProp;
use Inertia\Inertia;
use Inertia\MergeProp;
use Inertia\OnceProp;
use Inertia\OptionalProp;
use Inertia\ScrollProp;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use Spatie\LaravelData\Contracts\BaseData;

/**
 * Controllers pass only Data objects (or scalars) to Inertia, never arrays,
 * models or collections (spec §9.1 "Arch: controllers"). Checks the props of
 * `Inertia::render()` / `inertia()` and the value of each Inertia prop wrapper
 * (`Inertia::defer(fn () => …)` etc.).
 *
 * @implements Rule<CallLike>
 */
final class InertiaPropsRule implements Rule
{
    public const string IDENTIFIER = 'adminCore.inertiaProps';

    private const array WRAPPERS = ['optional', 'defer', 'merge', 'deepMerge', 'always', 'scroll', 'once'];

    private const array PROP_CLASSES = [AlwaysProp::class, DeferProp::class, MergeProp::class, OnceProp::class, OptionalProp::class, ScrollProp::class];

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! Controllers::contains($scope) || $node->isFirstClassCallable()) {
            return [];
        }

        $method = match (true) {
            $node instanceof StaticCall && $node->class instanceof Name && $node->class->toString() === Inertia::class && $node->name instanceof Identifier => $node->name->toString(),
            $node instanceof FuncCall && $node->name instanceof Name && $node->name->toLowerString() === 'inertia' => 'render',
            default => null,
        };
        $args = $node->getArgs();

        if ($method === 'render' && isset($args[1])) {
            return $this->checkProps($scope->getType($args[1]->value), $node->getStartLine());
        }

        if (in_array($method, self::WRAPPERS, true) && isset($args[0]) && ! $this->allowed($scope->getType($args[0]->value))) {
            return [$this->error("Inertia::{$method}() value", $scope->getType($args[0]->value), $node->getStartLine())];
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkProps(Type $props, int $line): array
    {
        if ((new ObjectType(BaseData::class))->isSuperTypeOf($props)->yes()) {
            return [];
        }

        if (! $props->isArray()->yes()) {
            return [$this->error('Inertia props', $props, $line)];
        }

        $errors = [];

        foreach ($props->getConstantArrays() ?: [] as $array) {
            foreach ($array->getKeyTypes() as $i => $key) {
                if (! $this->allowed($array->getValueTypes()[$i])) {
                    $errors[] = $this->error('Inertia prop '.$key->describe(VerbosityLevel::value()), $array->getValueTypes()[$i], $line);
                }
            }
        }

        if ($props->getConstantArrays() === [] && ! $this->allowed($props->getIterableValueType())) {
            $errors[] = $this->error('Inertia props', $props, $line);
        }

        return $errors;
    }

    private function allowed(Type $type): bool
    {
        foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $member) {
            $ok = match (true) {
                $member->isNull()->yes(), $member->isScalar()->yes() => true,
                $member instanceof ClosureType => $this->allowed($member->getReturnType()),
                default => (new ObjectType(BaseData::class))->isSuperTypeOf($member)->yes()
                    || array_any(self::PROP_CLASSES, fn (string $class): bool => (new ObjectType($class))->isSuperTypeOf($member)->yes()),
            };

            if (! $ok) {
                return false;
            }
        }

        return true;
    }

    private function error(string $what, Type $type, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message("{$what} is {$type->describe(VerbosityLevel::typeOnly())}; pass a laravel-data object instead (spec §9.1).")
            ->identifier(self::IDENTIFIER)
            ->line($line)
            ->build();
    }
}
