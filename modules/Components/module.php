<?php

declare(strict_types=1);

// Komponen: the living catalogue of the house style (AGENTS.md §6, DEVELOPER.md R21).
// Projects hide it with admin-core.modules.disabled.
return [
    'name' => 'Components',
    'slug' => 'components',
    'label' => 'Komponen',
    'icon' => 'blocks',
    'group' => 'sistem',
    'order' => 90,
    'enabled' => true,
    'core' => false,
    'superadmin_only' => true,
    'menu' => true,
    'actions' => ['view'],
    'widgets' => [],
];
