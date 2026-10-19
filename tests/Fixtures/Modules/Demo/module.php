<?php

declare(strict_types=1);

return [
    'name' => 'Demo',
    'slug' => 'demo',
    'label' => 'Demo',
    'icon' => 'receipt',
    'group' => 'master',
    'order' => 20,
    'enabled' => true,
    'core' => false,
    'superadmin_only' => false,
    'menu' => true,
    'actions' => ['view', 'create', 'approve'],
    'widgets' => [],
];
