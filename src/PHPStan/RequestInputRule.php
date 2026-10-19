<?php

declare(strict_types=1);

namespace Yuan\AdminCore\PHPStan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Controllers read input through laravel-data objects, never as a raw bag:
 * no `all()`, `validated()` or key-less `input()` on the request (spec §9.1
 * "Arch: controllers").
 *
 * @implements Rule<CallLike>
 */
final class RequestInputRule implements Rule
{
    public const string IDENTIFIER = 'adminCore.requestInput';

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! Controllers::contains($scope) || $node->isFirstClassCallable()
            || ! ($node instanceof MethodCall || $node instanceof StaticCall) || ! $node->name instanceof Identifier) {
            return [];
        }

        $method = $node->name->toString();
        $raw = $method === 'all' || $method === 'validated' || ($method === 'input' && $node->getArgs() === []);
        $onRequest = $node instanceof MethodCall
            ? (new ObjectType(Request::class))->isSuperTypeOf($scope->getType($node->var))->yes()
            : $node->class instanceof Name && $node->class->toString() === RequestFacade::class;

        if (! $raw || ! $onRequest) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Request {$method}() reads raw input; take a laravel-data object as the action's parameter instead (spec §9.1).")
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }
}
