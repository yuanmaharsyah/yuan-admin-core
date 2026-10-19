<?php

declare(strict_types=1);

return [
    'name' => 'AdminOnly',
    'slug' => 'admin-only',
    'label' => 'Admin Only',
    'icon' => 'shield',
    'group' => 'master',
    'order' => 20,
    'enabled' => true,
    'core' => false,
    'superadmin_only' => true,
    'menu' => true,
    'actions' => ['view'],
    'widgets' => [],
];
