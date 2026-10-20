<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Testing;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Assert;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Audit\Masker;
use Yuan\AdminCore\DataTable\VisibilityScope;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Permissions\PermissionSynchronizer;
use Yuan\AdminCore\Registry\ModuleRegistry;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\call;

/**
 * Module test helpers (DEVELOPER R11), called from Pest tests as
 * `ModuleAssertions::assertAudited(...)`. Permissions come from
 * `permissions:sync` and reach users only through a level.
 */
final class ModuleAssertions
{
    /**
     * Signs in a new active user whose level holds exactly these permissions.
     *
     * @param  list<string>  $permissions
     */
    public static function actingAsLevelWith(array $permissions): Model&Authenticatable
    {
        app(PermissionSynchronizer::class)->sync();
        $level = Level::query()->create(['name' => 'test-'.Str::lower(Str::random(8)), 'label' => 'Level Uji']);
        $level->syncPermissions($permissions);

        return self::actingAsLevel($level);
    }

    public static function actingAsSuperAdmin(): Model&Authenticatable
    {
        return self::actingAsLevel(Level::superAdmin());
    }

    public static function assertGuestRedirected(string $method, string $uri): void
    {
        app('auth')->forgetGuards();

        call(strtoupper($method), $uri)->assertRedirect(route('login'));
    }

    /**
     * 403 for a user holding every other permission of the module, so the
     * missing one is the reason.
     *
     * @param  array<string, mixed>  $data
     */
    public static function assertForbiddenWithout(string $permission, string $method, string $uri, array $data = []): void
    {
        [$slug, $action] = explode('.', $permission, 2) + [1 => ''];
        $module = app(ModuleRegistry::class)->find($slug);

        if ($module === null || ! in_array($action, $module->actions, true)) {
            throw new LogicException("[{$permission}] is not a declared module action.");
        }

        $others = array_map(fn (string $other): string => "{$slug}.{$other}", array_values(array_diff($module->actions, [$action])));
        self::actingAsLevelWith($others);

        call(strtoupper($method), $uri, $data)->assertForbidden();
    }

    /**
     * The latest `$event` row about `$subject` exists, and each `$masked` field
     * is stored per its `audit.masked_fields` rule, never raw.
     *
     * @param  list<string>  $masked
     */
    public static function assertAudited(Model $subject, string $event, array $masked = []): ImmutableActivity
    {
        $row = ImmutableActivity::query()->whereMorphedTo('subject', $subject)->where('event', $event)->latest('id')->first();
        Assert::assertNotNull($row, "No [{$event}] audit row for ".$subject::class." #{$subject->getKey()}.");

        $masker = app(Masker::class);
        $meta = $row->getProperty('meta');
        $places = [
            (array) $row->attribute_changes?->get('attributes'),
            (array) $row->attribute_changes?->get('old'),
            is_array($meta) ? $meta : [],
        ];

        foreach ($masked as $field) {
            $rule = $masker->rule($field);
            Assert::assertNotNull($rule, "[{$field}] is not in audit.masked_fields.");
            $values = array_column(array_filter($places, fn (array $place): bool => array_key_exists($field, $place)), $field);

            if ($rule === 'omit') {
                Assert::assertSame([], $values, "[{$field}] must never be stored.");

                continue;
            }

            Assert::assertNotSame([], $values, "[{$field}] is not in the [{$event}] row.");

            foreach ($values as $value) {
                Assert::assertTrue(
                    $value === null || $value === Masker::HIDDEN || ($rule === 'partial' && is_string($value) && preg_match('/^.{4}'.Masker::HIDDEN.'.{4}$/u', $value) === 1),
                    "[{$field}] is stored unmasked in the [{$event}] row.",
                );
            }
        }

        return $row;
    }

    /**
     * The request runs `$model::query()->visibleTo($user)` for the signed-in
     * user, so an "own unit only" rule on the model reaches this route (spec §7).
     *
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $data
     */
    public static function assertScopedToUser(string $model, string $method, string $uri, array $data = []): void
    {
        $user = auth()->id();
        Assert::assertNotNull($user, 'Sign in before assertScopedToUser().');

        $calls = VisibilityScope::record(fn () => call(strtoupper($method), $uri, $data));

        Assert::assertContains(
            ['model' => $model, 'user' => $user],
            $calls,
            strtoupper($method)." {$uri} never calls {$model}::query()->visibleTo() with the signed-in user.",
        );
    }

    /**
     * A non-SA viewer can't see the row through `visibleTo()`; Super Admin can.
     */
    public static function assertNotVisibleToNonSuperAdmin(ImmutableActivity $row): void
    {
        $visible = fn (Authenticatable $viewer): bool => ImmutableActivity::query()->visibleTo($viewer)->whereKey($row->getKey())->exists();

        Assert::assertFalse($visible(self::makeAdminUser()), "Audit row #{$row->getKey()} is visible to a non-Super Admin.");
        Assert::assertTrue($visible(self::makeAdminUser(Level::superAdmin())), "Audit row #{$row->getKey()} is hidden from Super Admin.");
    }

    private static function actingAsLevel(Level $level): Model&Authenticatable
    {
        $user = self::makeAdminUser($level);
        actingAs($user);

        return $user;
    }

    private static function makeAdminUser(?Level $level = null): Model&Authenticatable
    {
        $user = app(config()->string('auth.providers.users.model'));

        if (! $user instanceof Model || ! $user instanceof Authenticatable || ! method_exists($user, 'assignLevel')) {
            throw new LogicException('auth.providers.users.model must be an Eloquent user model with HasLevel.');
        }

        $user->forceFill(['name' => 'Penguji', 'email' => Str::lower(Str::random(12)).'@example.test', 'password' => Str::random(20), 'is_active' => true])->save();

        if ($level !== null) {
            $user->assignLevel($level);
        }

        return $user;
    }
}
