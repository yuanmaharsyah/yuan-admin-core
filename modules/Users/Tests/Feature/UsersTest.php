<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;
use Maatwebsite\Excel\Facades\Excel;
use Yuan\AdminCore\Events\UserCreated;
use Yuan\AdminCore\Modules\Users\Exports\UsersExport;
use Yuan\AdminCore\Permissions\Level;
use Yuan\AdminCore\Testing\ModuleAssertions;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

// The core Users module (frontend-behavior §8.1, AGENTS §8). Guard rails: UsersGuardRailsTest.

uses(RefreshDatabase::class);

function staffLevel(string $name = 'operator-uji'): Level
{
    return Level::query()->create(['name' => $name, 'guard_name' => 'web', 'label' => ucfirst($name)]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function userInput(array $overrides = []): array
{
    return [
        'name' => 'Siti Rahmawati',
        'username' => 'siti.rahmawati',
        'email' => 'siti@contoh.test',
        'level_id' => staffLevel('staf-'.random_int(1, 99999))->id,
        'is_active' => true,
        'password' => 'RahasiaBaru1',
        'password_confirmation' => 'RahasiaBaru1',
        ...$overrides,
    ];
}

function staffUser(): User
{
    $user = User::factory()->create(['name' => 'Andi Wijaya']);
    $user->assignLevel(staffLevel('staf-'.random_int(1, 99999)));

    return $user->refresh();
}

it('redirects guests to login', function (): void {
    $user = staffUser();

    foreach ([['get', '/admin/users'], ['get', '/admin/users/create'], ['post', '/admin/users'], ['get', "/admin/users/{$user->id}"], ['get', "/admin/users/{$user->id}/edit"], ['put', "/admin/users/{$user->id}"], ['delete', "/admin/users/{$user->id}"], ['post', "/admin/users/{$user->id}/deactivate"], ['post', "/admin/users/{$user->id}/reset-password"], ['get', '/admin/users/download']] as [$method, $uri]) {
        ModuleAssertions::assertGuestRedirected($method, $uri);
    }
});

it('needs the permission of each action', function (): void {
    $user = staffUser();

    ModuleAssertions::assertForbiddenWithout('users.view', 'get', '/admin/users');
    ModuleAssertions::assertForbiddenWithout('users.view', 'get', "/admin/users/{$user->id}");
    ModuleAssertions::assertForbiddenWithout('users.create', 'get', '/admin/users/create');
    ModuleAssertions::assertForbiddenWithout('users.create', 'post', '/admin/users', userInput());
    ModuleAssertions::assertForbiddenWithout('users.edit', 'get', "/admin/users/{$user->id}/edit");
    ModuleAssertions::assertForbiddenWithout('users.edit', 'put', "/admin/users/{$user->id}", userInput(['lock_version' => 0]));
    ModuleAssertions::assertForbiddenWithout('users.edit', 'post', "/admin/users/{$user->id}/deactivate");
    ModuleAssertions::assertForbiddenWithout('users.edit', 'post', "/admin/users/{$user->id}/reset-password", ['mode' => 'generate']);
    ModuleAssertions::assertForbiddenWithout('users.delete', 'delete', "/admin/users/{$user->id}");
    ModuleAssertions::assertForbiddenWithout('users.delete', 'delete', '/admin/users', ['ids' => [$user->id]]);
    ModuleAssertions::assertForbiddenWithout('users.download', 'get', '/admin/users/download');

    expect($user->fresh()?->is_active)->toBeTrue();
});

it('lists users with users.view, searched and filtered by level and status', function (): void {
    $staff = staffUser();
    User::factory()->inactive()->create(['name' => 'Rudi Hartono']);
    ModuleAssertions::actingAsLevelWith(['users.view']);

    AssertableInertia::fromTestResponse(get('/admin/users?search=andi')->assertOk())
        ->component('Users::Index')
        ->count('users.items', 1)
        ->where('users.items.0.level_label', $staff->level()?->label);

    AssertableInertia::fromTestResponse(get("/admin/users?filter[level]={$staff->level()?->id}"))->count('users.items', 1);
    AssertableInertia::fromTestResponse(get('/admin/users?filter[status]=0'))
        ->count('users.items', 1)
        ->where('users.items.0.name', 'Rudi Hartono');
});

it('creates a user who must change the password, with the level, audit and event', function (): void {
    Event::fake([UserCreated::class]);
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.create']);
    $input = userInput(['email' => 'SITI@contoh.test']);

    post('/admin/users', $input)->assertSessionHasNoErrors()->assertRedirect();

    $user = User::query()->where('email', 'siti@contoh.test')->sole();
    expect($user->must_change_password)->toBeTrue()
        ->and($user->username)->toBe('siti.rahmawati')
        ->and($user->level()?->id)->toBe($input['level_id'])
        ->and(Hash::check('RahasiaBaru1', $user->password))->toBeTrue();
    ModuleAssertions::assertAudited($user, 'created', masked: ['password']);
    ModuleAssertions::assertAudited($user, 'level_changed');
    Event::assertDispatchedTimes(UserCreated::class, 1);
});

it('validates the required fields and the username rule', function (): void {
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.create']);

    post('/admin/users', [])->assertSessionHasErrors(['name', 'email', 'level_id', 'password']);
    post('/admin/users', userInput(['username' => 'siti@contoh']))->assertSessionHasErrors(['username' => 'Username hanya boleh berisi huruf, angka, titik, garis bawah dan tanda hubung (tanpa "@").']);
    post('/admin/users', userInput(['password' => 'pendek', 'password_confirmation' => 'pendek']))->assertSessionHasErrors('password');
    post('/admin/users', userInput(['level_id' => 99999]))->assertSessionHasErrors('level_id');
});

it('shows and updates a user, auditing the change and the level change', function (): void {
    $user = staffUser();
    $other = staffLevel('pengawas');
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.edit']);

    AssertableInertia::fromTestResponse(get("/admin/users/{$user->id}")->assertOk())
        ->component('Users::Show')
        ->where('user.name', 'Andi Wijaya')
        ->where('user.is_self', false);

    put("/admin/users/{$user->id}", userInput(['name' => 'Andi W.', 'email' => $user->email, 'username' => $user->username, 'level_id' => $other->id, 'lock_version' => $user->lock_version]))
        ->assertSessionHasNoErrors()
        ->assertRedirect("/admin/users/{$user->id}");

    expect($user->fresh()?->name)->toBe('Andi W.')
        ->and($user->fresh()?->level()?->is($other))->toBeTrue();
    ModuleAssertions::assertAudited($user, 'updated');
    ModuleAssertions::assertAudited($user, 'level_changed');
});

it('gives the second of two saves of the same version a 409', function (): void {
    $user = staffUser();
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.edit']);
    $input = userInput(['email' => $user->email, 'username' => $user->username, 'level_id' => $user->level()?->id, 'lock_version' => $user->lock_version]);

    put("/admin/users/{$user->id}", [...$input, 'name' => 'Satu'])->assertRedirect();
    put("/admin/users/{$user->id}", [...$input, 'name' => 'Dua'])->assertStatus(409);

    expect($user->fresh()?->name)->toBe('Satu');
});

it('deactivates and activates a user, auditing both and ending their sessions', function (): void {
    $user = staffUser();
    $version = $user->session_version;
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.edit']);

    post("/admin/users/{$user->id}/deactivate")->assertRedirect();
    expect($user->fresh()?->is_active)->toBeFalse()
        ->and($user->fresh()?->session_version)->toBeGreaterThan($version);
    ModuleAssertions::assertAudited($user, 'user_deactivated');

    post("/admin/users/{$user->id}/activate")->assertRedirect();
    expect($user->fresh()?->is_active)->toBeTrue();
    ModuleAssertions::assertAudited($user, 'user_activated');
});

it('resets a password to a temporary one shown once, or to one the admin sets', function (): void {
    $user = staffUser();
    $version = $user->session_version;
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.edit']);

    post("/admin/users/{$user->id}/reset-password", ['mode' => 'generate'])->assertRedirect();
    $secret = session(SessionKey::FLASH_DATA)['secret'] ?? '';

    expect($secret)->toMatch('/^[A-Za-z2-9]{12}$/')
        ->and(Hash::check($secret, (string) $user->fresh()?->password))->toBeTrue()
        ->and($user->fresh()?->must_change_password)->toBeTrue()
        ->and($user->fresh()?->session_version)->toBeGreaterThan($version);
    ModuleAssertions::assertAudited($user, 'password_reset');
    get("/admin/users/{$user->id}")->assertOk();

    post("/admin/users/{$user->id}/reset-password", ['mode' => 'set', 'password' => 'DitentukanAdmin9', 'password_confirmation' => 'DitentukanAdmin9'])->assertRedirect();
    expect(Hash::check('DitentukanAdmin9', (string) $user->fresh()?->password))->toBeTrue()
        ->and(session(SessionKey::FLASH_DATA))->not->toHaveKey('secret');

    post("/admin/users/{$user->id}/reset-password", ['mode' => 'set', 'password' => 'lemah'])->assertSessionHasErrors('password');
});

it('soft-deletes a user into the trash and audits it', function (): void {
    $user = staffUser();
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.delete']);

    delete("/admin/users/{$user->id}")->assertRedirect('/admin/users');

    expect(User::query()->find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id)?->trashed())->toBeTrue();
    ModuleAssertions::assertAudited($user, 'deleted');
    get('/admin/users/trash')->assertForbidden();
});

it('downloads the list and audits it', function (): void {
    Excel::fake();
    staffUser();
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.download']);

    get('/admin/users/download')->assertOk();

    Excel::assertDownloaded('users-'.now()->format('Ymd-His').'.xlsx', fn (UsersExport $export): bool => $export->query()->count() === 2);
});

it('scopes every user query to the signed-in user', function (): void {
    $user = staffUser();
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.edit', 'users.delete', 'users.download']);
    $input = userInput(['email' => $user->email, 'username' => $user->username, 'level_id' => $user->level()?->id, 'lock_version' => $user->lock_version]);

    foreach ([['get', '/admin/users', []], ['get', "/admin/users/{$user->id}", []], ['get', "/admin/users/{$user->id}/edit", []], ['put', "/admin/users/{$user->id}", $input], ['post', "/admin/users/{$user->id}/deactivate", []], ['post', "/admin/users/{$user->id}/reset-password", ['mode' => 'generate']], ['delete', '/admin/users', ['ids' => [$user->id]]], ['delete', "/admin/users/{$user->id}", []]] as [$method, $uri, $data]) {
        ModuleAssertions::assertScopedToUser(User::class, $method, $uri, $data);
    }
});
