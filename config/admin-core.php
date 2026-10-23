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
        // Route name of the signed-in home page (error pages link back to it).
        'home' => 'dashboard',
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

        // Folder of the package's own `Core::` pages (auth, errors), as seen
        // from the project, so Vite entries are relative to the project root.
        'core_path' => base_path('vendor/yuan/admin-core/resources/js/pages'),
    ],

    // Password rules (spec §8), applied as Password::defaults(): min length,
    // mixed case and a number. uncompromised() calls an online API, so it is
    // off by default for offline servers.
    'passwords' => [
        'min' => 10,
        'uncompromised' => false,
    ],

    // The swappable User model (spec §2.2): the project's class, which extends
    // Yuan\AdminCore\Modules\Users\Models\User. Keep it equal to
    // auth.providers.users.model.
    'models' => [
        'user' => 'App\\Models\\User',
    ],

    'users' => [
        // A class implementing Yuan\AdminCore\Users\UserFormExtension: extra
        // fields (columns the project adds to `users` with its own migration)
        // in the Users forms, their validation and the detail page. Null: none.
        'form_extension' => null,
    ],

    // List downloads (spec §7, ExportManager). Up to `queue_threshold` rows the
    // file streams at once; above it an ExportJob writes it to `disk` and it
    // stays downloadable for `ttl_hours`. Settings overrides the threshold in
    // Phase 07.
    'exports' => [
        'queue_threshold' => (int) env('ADMIN_EXPORT_QUEUE_THRESHOLD', 5000),
        'disk' => 'local',
        'ttl_hours' => 24,
    ],

    // Files attached to records (HasAttachments): a private disk only, served
    // through signed URLs valid for `url_ttl_minutes`. A model may narrow
    // `mimes` and `max_kb` in attachmentRules().
    'attachments' => [
        'disk' => 'local',
        'max_kb' => 10240,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'docx'],
        'url_ttl_minutes' => 5,
    ],

    // A full-width notice under the topbar for every signed-in user; null hides
    // it. Settings overrides it from Phase 07.
    'maintenance_banner' => env('ADMIN_MAINTENANCE_BANNER'),

];
