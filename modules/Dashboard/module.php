<?php

declare(strict_types=1);

return [
    'name' => 'Dashboard',
    'slug' => 'dashboard',
    'label' => 'Beranda',
    'icon' => 'layout-dashboard',
    'group' => 'beranda',
    'order' => 0,
    'enabled' => true,
    'core' => true,
    'superadmin_only' => false,
    'menu' => true,
    'actions' => ['view'],
    'widgets' => [],
];
