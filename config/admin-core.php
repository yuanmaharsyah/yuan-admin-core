<?php

declare(strict_types=1);

return [

    'modules' => [
        // Module roots, scanned in this order. `namespace` is the PSR-4 prefix of
        // the root; a module's classes live under `{namespace}{Name}\`.
        'roots' => [
            [
                'name' => 'core',
                'path' => base_path('vendor/yuan/admin-core/modules'),
                'namespace' => 'Yuan\\AdminCore\\Modules\\',
            ],
            [
                'name' => 'project',
                'path' => base_path('modules'),
                'namespace' => 'Modules\\',
            ],
        ],

        // Slugs of modules in the "core" root to turn off without editing
        // vendor/. Modules with 'core' => true cannot be listed here. Project
        // modules use 'enabled' in their own module.php.
        'disabled' => [],

        // Written by `php artisan modules:cache`, removed by `modules:clear`.
        'cache_path' => base_path('bootstrap/cache/admin-modules.php'),
    ],

    'routes' => [
        'prefix' => 'admin',
        // `active` logs out deactivated users and revoked sessions.
        'middleware' => ['web', 'auth', 'active'],
    ],

    'menu' => [
        'groups' => [
            'beranda' => ['label' => 'Beranda', 'icon' => 'house', 'order' => 0],
            'master' => ['label' => 'Master Data', 'icon' => 'database', 'order' => 10],
            'keuangan' => ['label' => 'Keuangan', 'icon' => 'wallet', 'order' => 20],
            'sistem' => ['label' => 'Sistem', 'icon' => 'settings', 'order' => 90],
        ],
    ],

    'audit' => [
        // Personal data in audit rows (spec §7.1, UU PDP), checked against every
        // key of the logged changes and meta. Patterns may use `*`; the first
        // match wins. omit = never stored, partial = first 4 and last 4
        // characters kept, full = stored as ••••. Add project fields here.
        'masked_fields' => [
            'password' => 'omit',
            'password_confirmation' => 'omit',
            'remember_token' => 'omit',
            '*_token' => 'omit',
            'secret' => 'omit',
            'nik' => 'partial',
            'no_kk' => 'partial',
            'npwp' => 'partial',
            'no_rekening' => 'partial',
            'no_hp' => 'full',
            'alamat' => 'full',
        ],
    ],

    'seed' => [
        // Password of the two seeded Super Admins. Empty: each gets a random
        // password printed once by the seeder (refused in production).
        'password' => env('ADMIN_SEED_PASSWORD'),
    ],

    'pages' => [
        // Page name => replacement page name, for example Users::Index to
        // App::Users/Index (resources/js/pages/Users/Index.tsx in the project).
        'overrides' => [],
    ],

];
