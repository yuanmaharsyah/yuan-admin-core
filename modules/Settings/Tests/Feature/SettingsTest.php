<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Yuan\AdminCore\Audit\ImmutableActivity;
use Yuan\AdminCore\Settings\Settings;
use Yuan\AdminCore\Testing\ModuleAssertions;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

// Pengaturan (spec §7.3): Super Admin only, never grantable; one tab per
// registry group, typed validation, one audited save per tab.

uses(RefreshDatabase::class);

it('redirects guests to login', function (): void {
    ModuleAssertions::assertGuestRedirected('get', '/admin/settings');
    ModuleAssertions::assertGuestRedirected('put', '/admin/settings/kontak');
});

it('refuses every other level, even one holding every permission there is', function (): void {
    ModuleAssertions::actingAsLevelWith(['users.view', 'users.edit', 'audit-log.view', 'audit-log.download', 'dashboard.view']);

    get('/admin/settings')->assertForbidden();
    put('/admin/settings/kontak', ['lock_version' => 0, 'values' => ['kode_pos' => '40115']])->assertForbidden();

    expect(settings('kontak.kode_pos'))->toBeNull();
});

it('renders every tab from the registry with its fields and version', function (): void {
    ModuleAssertions::actingAsSuperAdmin();
    app(Settings::class)->save('kontak', ['telepon' => '081234567890']);

    AssertableInertia::fromTestResponse(get('/admin/settings?tab=kontak')->assertOk())
        ->component('Settings::Index')
        ->where('tab', 'kontak')
        ->where('tabs.0.key', 'umum')
        ->where('tabs.0.fields.1.type', 'image')
        ->where('tabs.0.fields.1.accept', ['png', 'ico'])
        ->where('tabs.1.key', 'kontak')
        ->where('tabs.1.lock_version', 1)
        ->where('tabs.1.fields.0.value', '+62 812-3456-7890')
        ->where('tabs.3.fields.0.options.1.value', 'maintenance')
        ->has('tabs', 6);
});

it('validates each field by its type and names it in Indonesian', function (): void {
    ModuleAssertions::actingAsSuperAdmin();

    put('/admin/settings/kontak', ['lock_version' => 0, 'values' => ['telepon' => '12345', 'whatsapp' => '021 1234 5678', 'kode_pos' => '401']])
        ->assertSessionHasErrors(['values.telepon', 'values.whatsapp', 'values.kode_pos']);
    put('/admin/settings/sosial', ['lock_version' => 0, 'values' => ['instagram' => 'https://facebook.com/dinas']])
        ->assertSessionHasErrors(['values.instagram' => 'Isi alamat instagram atau nama akunnya saja.']);
    put('/admin/settings/akses', ['lock_version' => 0, 'values' => ['mode' => 'closed']])
        ->assertSessionHasErrors(['values.mode']);

    expect(ImmutableActivity::query()->where('event', 'settings_updated')->exists())->toBeFalse();
});

it('saves a tab, audits the change and flashes success', function (): void {
    ModuleAssertions::actingAsSuperAdmin();

    put('/admin/settings/sosial', ['lock_version' => 0, 'values' => ['linkedin' => 'dinas-kota', 'x' => '']])
        ->assertRedirect('/admin/settings?tab=sosial')
        ->assertInertiaFlash('success', 'Pengaturan disimpan.');

    expect(settings('sosial.linkedin'))->toBe('https://linkedin.com/company/dinas-kota')
        ->and(ImmutableActivity::query()->where('event', 'settings_updated')->sole()->properties?->get('meta')['new'])->toBe(['sosial' => ['linkedin' => 'https://linkedin.com/company/dinas-kota']]);
});

it('answers 409 when the tab changed since the form loaded', function (): void {
    ModuleAssertions::actingAsSuperAdmin();
    app(Settings::class)->save('kontak', ['kode_pos' => '40115']);

    put('/admin/settings/kontak', ['lock_version' => 0, 'values' => ['kode_pos' => '40116']])->assertStatus(409);

    expect(settings('kontak.kode_pos'))->toBe('40115');
});

it('uploads a logo with the form (POST + _method) and keeps it when the field is left empty', function (): void {
    Storage::fake('public');
    ModuleAssertions::actingAsSuperAdmin();

    post('/admin/settings/umum?_method=PUT', ['lock_version' => 0, 'values' => ['nama_aplikasi' => 'SIM Dinas', 'logo_persegi' => UploadedFile::fake()->image('logo.png', 256, 256)]])
        ->assertRedirect('/admin/settings?tab=umum');
    $logo = settings('umum.logo_persegi');
    put('/admin/settings/umum', ['lock_version' => app(Settings::class)->version('umum'), 'values' => ['nama_aplikasi' => 'SIM Dinas Kota', 'logo_persegi' => null]])->assertRedirect();

    expect($logo)->toBeString()
        ->and(settings('umum.logo_persegi'))->toBe($logo);
    Storage::disk('public')->assertExists((string) $logo);

    post('/admin/settings/umum?_method=PUT', ['lock_version' => app(Settings::class)->version('umum'), 'values' => ['logo_persegi' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')]])
        ->assertSessionHasErrors(['values.logo_persegi']);
});

it('shares the stored name, logos and favicon with every page', function (): void {
    Storage::fake('public');
    ModuleAssertions::actingAsSuperAdmin();
    app(Settings::class)->save('umum', [
        'nama_aplikasi' => 'SIM Dinas',
        'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
        'logo_persegi' => UploadedFile::fake()->image('logo.png', 256, 256),
        'logo_lanskap' => UploadedFile::fake()->image('logo-wide.png', 300, 100),
    ]);
    app(Settings::class)->applyToConfig();
    $url = fn (string $key): string => Storage::disk('public')->url((string) settings($key));

    $response = get('/admin/settings')->assertOk();

    AssertableInertia::fromTestResponse($response)
        ->where('app.name', 'SIM Dinas')
        ->where('app.logo', $url('umum.logo_persegi'))
        ->where('app.logo_landscape', $url('umum.logo_lanskap'))
        ->where('app.favicon', $url('umum.favicon'));
    $response->assertSee('<link rel="icon" href="'.$url('umum.favicon').'">', false)
        ->assertSee('<meta name="application-name" content="SIM Dinas">', false);
});

it('shows a setting a project declares, and saves it', function (): void {
    config(['admin-core.settings.proyek' => ['label' => 'Proyek', 'fields' => ['kode_instansi' => ['type' => 'string', 'label' => 'Kode instansi', 'rules' => ['max:10']]]]]);
    app()->forgetInstance(Settings::class);
    ModuleAssertions::actingAsSuperAdmin();

    AssertableInertia::fromTestResponse(get('/admin/settings?tab=proyek'))
        ->where('tab', 'proyek')
        ->where('tabs.6.fields.0.label', 'Kode instansi');
    put('/admin/settings/proyek', ['lock_version' => 0, 'values' => ['kode_instansi' => 'DK-00000001']])->assertSessionHasErrors(['values.kode_instansi']);
    put('/admin/settings/proyek', ['lock_version' => 0, 'values' => ['kode_instansi' => 'DK-01']])->assertRedirect();

    expect(settings('proyek.kode_instansi'))->toBe('DK-01');
});

it('answers 404 for a tab the registry does not have', function (): void {
    ModuleAssertions::actingAsSuperAdmin();

    put('/admin/settings/rahasia', ['lock_version' => 0, 'values' => []])->assertNotFound();
});
