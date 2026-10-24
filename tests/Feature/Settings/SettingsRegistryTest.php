<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yuan\AdminCore\Concurrency\StaleUpdateException;
use Yuan\AdminCore\Settings\BrandingStorage;
use Yuan\AdminCore\Settings\SettingDefinition;
use Yuan\AdminCore\Settings\Settings;

// The settings registry (spec §7.3): typed values, one audited save per tab,
// cached until a save, stale-checked per tab.

uses(RefreshDatabase::class);

function settingsRegistry(): Settings
{
    return app(Settings::class);
}

/**
 * The stored form of an input, or false when the type's rules refuse it.
 */
function stored(string $key, string $input): mixed
{
    $definition = settingsRegistry()->definition($key);

    return Validator::make(['value' => $input], ['value' => $definition->type->rules($definition)])->fails()
        ? false
        : $definition->type->normalize($input, $definition);
}

/** A 16×16 ICO holding one PNG image (the Vista format). */
function icoFile(): UploadedFile
{
    $image = imagecreatetruecolor(16, 16);
    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();
    $ico = pack('vvv', 0, 1, 1).pack('CCCCvvVV', 16, 16, 0, 0, 1, 32, strlen($png), 22).$png;
    $path = tempnam(sys_get_temp_dir(), 'ico');
    file_put_contents($path, $ico);

    return new UploadedFile($path, 'favicon.ico', null, null, true);
}

function brandingFails(string $key, UploadedFile $file): bool
{
    return Validator::make(['file' => $file], ['file' => BrandingStorage::rules($key)])->fails();
}

it('stores a phone number as E.164 and shows it grouped', function (): void {
    expect(stored('kontak.telepon', '0812-3456-7890'))->toBe('+6281234567890')
        ->and(stored('kontak.telepon', '(021) 1234 5678'))->toBe('+622112345678')
        ->and(stored('kontak.telepon', '+62 812 3456 7890'))->toBe('+6281234567890')
        ->and(stored('kontak.telepon', '6281234567890'))->toBe('+6281234567890')
        ->and(stored('kontak.telepon', '12345'))->toBeFalse()
        ->and(stored('kontak.telepon', '0012345678'))->toBeFalse()
        ->and(settingsRegistry()->definition('kontak.telepon')->type->display('+6281234567890'))->toBe('+62 812-3456-7890');
});

it('stores WhatsApp as digits for mobile numbers only and exposes a wa.me link', function (): void {
    $type = settingsRegistry()->definition('kontak.whatsapp')->type;

    expect(stored('kontak.whatsapp', '0812 3456 7890'))->toBe('6281234567890')
        ->and(stored('kontak.whatsapp', '+62 812-3456-7890'))->toBe('6281234567890')
        ->and(stored('kontak.whatsapp', '021 1234 5678'))->toBeFalse()
        ->and($type->publicValue('6281234567890'))->toBe('https://wa.me/6281234567890');
});

it('takes a postal code of exactly five digits', function (): void {
    expect(stored('kontak.kode_pos', '40115'))->toBe('40115')
        ->and(stored('kontak.kode_pos', '4011'))->toBeFalse()
        ->and(stored('kontak.kode_pos', '40115a'))->toBeFalse();
});

it('keeps a social URL on its own platform, refuses other domains and turns a handle into the URL', function (string $platform, string $url, string $normalised, string $handle, string $profile): void {
    expect(stored("sosial.{$platform}", $url))->toBe($normalised)
        ->and(stored("sosial.{$platform}", 'https://evil.example/akun'))->toBeFalse()
        ->and(stored("sosial.{$platform}", $handle))->toBe($profile);
})->with([
    'instagram' => ['instagram', 'https://www.instagram.com/dinas.kota/', 'https://instagram.com/dinas.kota', '@dinas.kota', 'https://instagram.com/dinas.kota'],
    'x' => ['x', 'twitter.com/dinaskota', 'https://x.com/dinaskota', 'dinaskota', 'https://x.com/dinaskota'],
    'tiktok' => ['tiktok', 'https://www.tiktok.com/@dinaskota', 'https://tiktok.com/@dinaskota', '@dinaskota', 'https://www.tiktok.com/@dinaskota'],
    'facebook' => ['facebook', 'https://m.facebook.com/dinaskota', 'https://facebook.com/dinaskota', 'dinaskota', 'https://facebook.com/dinaskota'],
    'youtube' => ['youtube', 'https://youtube.com/@dinaskota', 'https://youtube.com/@dinaskota', '@dinaskota', 'https://youtube.com/@dinaskota'],
    'linkedin' => ['linkedin', 'https://www.linkedin.com/in/budi-santoso', 'https://linkedin.com/in/budi-santoso', 'dinas-kota', 'https://linkedin.com/company/dinas-kota'],
]);

it('saves a tab with one settings_updated row holding the old and new values, masked fields masked', function (): void {
    settingsRegistry()->save('kontak', ['alamat' => 'Jl. Merdeka 1', 'kode_pos' => '40115']);
    $changed = settingsRegistry()->save('kontak', ['alamat' => 'Jl. Merdeka 2', 'kode_pos' => '40115', 'telepon' => '0812 3456 7890']);
    $row = latestActivity();

    expect($changed)->toBe(['kontak.telepon', 'kontak.alamat'])
        ->and($row->event)->toBe('settings_updated')
        ->and($row->properties?->get('meta'))->toBe([
            'old' => ['kontak' => ['telepon' => null, 'alamat' => '••••']],
            'new' => ['kontak' => ['telepon' => '+6281234567890', 'alamat' => '••••']],
        ])
        ->and(settingsRegistry()->save('kontak', ['kode_pos' => '40115']))->toBe([]);
});

it('reads through the cache and busts it on save; a key with nothing stored gives its default', function (): void {
    expect(settings('akses.mode'))->toBe('open');

    settingsRegistry()->save('akses', ['mode' => 'maintenance']);

    expect(settings('akses.mode'))->toBe('maintenance')
        ->and(cache()->get(Settings::CACHE_KEY))->toBe(['akses.mode' => 'maintenance']);
});

it('refuses a save from a form that loaded an older version of the tab', function (): void {
    $version = settingsRegistry()->version('kontak');
    settingsRegistry()->save('kontak', ['kode_pos' => '40115'], $version);

    expect(settingsRegistry()->version('kontak'))->toBe($version + 1)
        ->and(fn () => settingsRegistry()->save('kontak', ['kode_pos' => '40116'], $version))->toThrow(StaleUpdateException::class);
});

it('takes a setting a project declares, in its own tab', function (): void {
    config(['admin-core.settings.proyek' => ['label' => 'Proyek', 'fields' => ['kode_instansi' => ['type' => 'string', 'label' => 'Kode instansi', 'rules' => ['max:10']]]]]);
    app()->forgetInstance(Settings::class);

    settingsRegistry()->save('proyek', ['kode_instansi' => 'DK-01']);

    expect(settingsRegistry()->groups())->toHaveKey('proyek')
        ->and(settings('proyek.kode_instansi'))->toBe('DK-01');
});

it('refuses an image setting outside the three branding fields', function (): void {
    expect(fn () => SettingDefinition::fromConfig('proyek', 'foto', ['type' => 'image']))->toThrow(InvalidArgumentException::class, 'only the branding fields may be images');
});

it('applies stored values over the config they override', function (): void {
    settingsRegistry()->save('umum', ['nama_aplikasi' => 'SIM Dinas']);
    settingsRegistry()->save('keamanan', ['session_lifetime' => '45']);
    settingsRegistry()->save('ekspor', ['queue_threshold' => '200']);

    settingsRegistry()->applyToConfig();

    expect(config('app.name'))->toBe('SIM Dinas')
        ->and(config('session.lifetime'))->toBe(45)
        ->and(config('admin-core.exports.queue_threshold'))->toBe(200);
});

it('accepts each branding type, refuses SVG, oversized and wrongly shaped images', function (): void {
    expect(brandingFails('umum.favicon', UploadedFile::fake()->image('favicon.png', 32, 32)))->toBeFalse()
        ->and(brandingFails('umum.favicon', icoFile()))->toBeFalse()
        ->and(brandingFails('umum.logo_persegi', UploadedFile::fake()->image('logo.jpg', 256, 256)))->toBeFalse()
        ->and(brandingFails('umum.logo_persegi', UploadedFile::fake()->image('logo.webp', 256, 256)))->toBeFalse()
        ->and(brandingFails('umum.logo_lanskap', UploadedFile::fake()->image('logo.png', 300, 100)))->toBeFalse()
        ->and(brandingFails('umum.logo_persegi', UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"/>')))->toBeTrue()
        ->and(brandingFails('umum.logo_persegi', UploadedFile::fake()->createWithContent('logo.png', '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"/>')))->toBeTrue()
        ->and(brandingFails('umum.favicon', UploadedFile::fake()->image('favicon.png', 32, 32)->size(300)))->toBeTrue()
        ->and(brandingFails('umum.logo_persegi', UploadedFile::fake()->image('logo.png', 256, 200)))->toBeTrue()
        ->and(brandingFails('umum.logo_persegi', UploadedFile::fake()->image('logo.png', 64, 64)))->toBeTrue()
        ->and(brandingFails('umum.logo_lanskap', UploadedFile::fake()->image('logo.png', 120, 100)))->toBeTrue();
});

it('stores a branding upload under a random name on the public disk and deletes the file it replaces', function (): void {
    Storage::fake('public');

    settingsRegistry()->save('umum', ['logo_persegi' => UploadedFile::fake()->image('logo-dinas.png', 256, 256)]);
    $first = settings('umum.logo_persegi');
    settingsRegistry()->save('umum', ['logo_persegi' => UploadedFile::fake()->image('logo-dinas.png', 256, 256)]);
    $second = settings('umum.logo_persegi');

    expect($first)->toBeString()->toStartWith('branding/')
        ->and($first)->not->toContain('logo-dinas')
        ->and($second)->not->toBe($first);
    Storage::disk('public')->assertMissing((string) $first);
    Storage::disk('public')->assertExists((string) $second);
    expect(settingsRegistry()->publicValues()['umum.logo_persegi'])->toBe(Storage::disk('public')->url((string) $second));
});

it('gives the defaults, without caching them, before the tables exist (artisan migrate must still boot)', function (): void {
    Schema::drop('settings');
    cache()->flush();

    expect(settings('akses.mode'))->toBe('open')
        ->and(cache()->has(Settings::CACHE_KEY))->toBeFalse();

    // A database cache store whose table isn't migrated yet.
    config(['cache.default' => 'database', 'cache.stores.database.table' => 'cache_not_migrated']);

    expect(settings('akses.mode'))->toBe('open');
});
