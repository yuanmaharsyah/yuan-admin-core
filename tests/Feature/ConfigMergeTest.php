<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Yuan\AdminCore\AdminCoreServiceProvider;

// A project's published config/admin-core.php is merged deeply over the
// package defaults, so nested keys added by later versions still arrive.

/**
 * The package config as a project published it, with these changes.
 *
 * @param  array<string, mixed>  $project
 */
function mergedAdminConfig(array $project): Repository
{
    $config = new Repository(['admin-core' => $project]);
    AdminCoreServiceProvider::mergeConfig($config);

    return $config;
}

it('fills a nested default the published file lacks', function (): void {
    $published = require __DIR__.'/../../config/admin-core.php';
    unset($published['exports']['queue_threshold'], $published['settings']['kontak']['fields']['kode_pos']);

    $config = mergedAdminConfig($published);

    expect($config->get('admin-core.exports.queue_threshold'))->toBe(5000)
        ->and($config->get('admin-core.settings.kontak.fields.kode_pos.type'))->toBe('postal_code');
});

it('keeps what the project sets, at any depth', function (): void {
    $config = mergedAdminConfig([
        'exports' => ['queue_threshold' => 100],
        'settings' => ['proyek' => ['label' => 'Proyek', 'fields' => []]],
        'audit' => ['masked_fields' => ['nik' => 'partial']],
    ]);

    expect($config->get('admin-core.exports.queue_threshold'))->toBe(100)
        ->and($config->get('admin-core.exports.disk'))->toBe('local')
        ->and($config->get('admin-core.settings.proyek.label'))->toBe('Proyek')
        ->and($config->get('admin-core.settings.umum.label'))->toBe('Umum')
        ->and($config->get('admin-core.audit.masked_fields.nik'))->toBe('partial')
        ->and($config->get('admin-core.audit.masked_fields.password'))->toBe('omit');
});

it('replaces lists whole instead of merging them by index', function (): void {
    $config = mergedAdminConfig([
        'attachments' => ['mimes' => ['pdf']],
        'routes' => ['middleware' => ['web', 'auth']],
    ]);

    expect($config->get('admin-core.attachments.mimes'))->toBe(['pdf'])
        ->and($config->get('admin-core.routes.middleware'))->toBe(['web', 'auth']);
});

it('merges the published file at boot', function (): void {
    expect(config('admin-core.exports.disk'))->toBe('local')
        ->and(config('admin-core.settings.umum.fields.nama_aplikasi.type'))->toBe('string');
});
