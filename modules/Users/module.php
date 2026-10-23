<?php

declare(strict_types=1);

return [
    'name' => 'Users',
    'slug' => 'users',
    'label' => 'Users',
    'icon' => 'users',
    'group' => 'master',
    'order' => 0,
    'enabled' => true,
    'core' => true,
    'superadmin_only' => false,
    'menu' => true,
    'actions' => ['view', 'create', 'edit', 'delete', 'download'],
    'widgets' => [],
];
