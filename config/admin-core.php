<?php

declare(strict_types=1);

return [

    'modules' => [
        // Module roots, scanned in this order. `namespace` is the PSR-4 prefix of
        // the root; a module's classes live under `{namespace}{Name}\`.
        'roots' => [
            [
                'name' => 'core',
                'path' => base_path('vendor/yuanmaharsyah/admin-core/modules'),
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
        // Days of audit rows to keep; `admin:audit-prune` (scheduled daily)
        // deletes older ones and logs one audit_pruned row. Null keeps everything.
        'retention_days' => env('ADMIN_AUDIT_RETENTION_DAYS'),
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
        'core_path' => base_path('vendor/yuanmaharsyah/admin-core/resources/js/pages'),
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
    // stays downloadable for `ttl_hours`. Settings → Ekspor overrides the
    // threshold.
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

    // Beranda (spec §7.2): at most this many widgets on the page.
    'dashboard' => [
        'max_widgets' => 12,
    ],

    'branding' => [
        // A static file in the project's public/ (e.g. 'images/logo.png'): the
        // logo on the login card and the rail while Settings has no 1:1 logo.
        // Null: the app name only.
        'logo' => env('ADMIN_BRANDING_LOGO'),
    ],

    // The settings registry (spec §7.3): tab => label and fields. A field has
    // a type (string, text, integer, boolean, enum + options, phone, whatsapp,
    // postal_code, social (named after its platform), image (the three
    // branding fields only)), a label, optional help, extra `rules`, a
    // `default` (used while nothing is stored; null falls back to the config
    // noted) and `public` (shared with every page through AppData). Projects
    // add fields or tabs the same way; read a value with settings('tab.field').
    'settings' => [
        'umum' => ['label' => 'Umum', 'fields' => [
            'nama_aplikasi' => ['type' => 'string', 'label' => 'Nama aplikasi', 'rules' => ['max:60'], 'public' => true, 'help' => 'Kosong: memakai APP_NAME.'],
            'favicon' => ['type' => 'image', 'label' => 'Favicon', 'public' => true, 'help' => 'PNG atau ICO, persegi, maksimal 256 KB.'],
            'logo_persegi' => ['type' => 'image', 'label' => 'Logo 1:1', 'public' => true, 'help' => 'PNG, JPG atau WebP, persegi, minimal 128 px, maksimal 1 MB.'],
            'logo_lanskap' => ['type' => 'image', 'label' => 'Logo lanskap', 'public' => true, 'help' => 'PNG, JPG atau WebP, lebar minimal 1,5 × tinggi, tinggi minimal 32 px, maksimal 1 MB.'],
        ]],
        'kontak' => ['label' => 'Kontak', 'fields' => [
            'telepon' => ['type' => 'phone', 'label' => 'Telepon', 'public' => true, 'help' => 'Seluler (08…), kantor (021…) atau +62….'],
            'whatsapp' => ['type' => 'whatsapp', 'label' => 'WhatsApp', 'public' => true, 'help' => 'Nomor seluler, mis. 0812 3456 7890.'],
            'alamat' => ['type' => 'text', 'label' => 'Alamat', 'public' => true],
            'kode_pos' => ['type' => 'postal_code', 'label' => 'Kode pos', 'public' => true],
        ]],
        'sosial' => ['label' => 'Sosial Media', 'fields' => [
            'instagram' => ['type' => 'social', 'label' => 'Instagram', 'public' => true],
            'x' => ['type' => 'social', 'label' => 'X', 'public' => true],
            'tiktok' => ['type' => 'social', 'label' => 'TikTok', 'public' => true],
            'facebook' => ['type' => 'social', 'label' => 'Facebook', 'public' => true],
            'youtube' => ['type' => 'social', 'label' => 'YouTube', 'public' => true],
            'linkedin' => ['type' => 'social', 'label' => 'LinkedIn', 'public' => true],
        ]],
        'akses' => ['label' => 'Akses Situs', 'fields' => [
            'mode' => ['type' => 'enum', 'label' => 'Mode', 'default' => 'open', 'options' => ['open' => 'Terbuka', 'maintenance' => 'Pemeliharaan', 'coming_soon' => 'Segera hadir'], 'help' => 'Hanya halaman publik. Login dan /admin selalu bisa dibuka.'],
            'pesan' => ['type' => 'text', 'label' => 'Pesan', 'rules' => ['max:500']],
        ]],
        'keamanan' => ['label' => 'Keamanan', 'fields' => [
            'session_lifetime' => ['type' => 'integer', 'label' => 'Lama sesi (menit)', 'rules' => ['min:5', 'max:43200'], 'help' => 'Kosong: memakai SESSION_LIFETIME.'],
        ]],
        'ekspor' => ['label' => 'Ekspor', 'fields' => [
            'queue_threshold' => ['type' => 'integer', 'label' => 'Batas unduhan langsung (baris)', 'rules' => ['min:1', 'max:1000000'], 'help' => 'Di atas batas ini unduhan diantrekan. Kosong: memakai ADMIN_EXPORT_QUEUE_THRESHOLD.'],
        ]],
    ],

];
