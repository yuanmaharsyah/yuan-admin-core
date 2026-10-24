<?php

declare(strict_types=1);

return [
    'name' => 'AuditLog',
    'slug' => 'audit-log',
    'label' => 'Log Aktivitas',
    'icon' => 'history',
    'group' => 'sistem',
    'order' => 20,
    'enabled' => true,
    'core' => true,
    'superadmin_only' => false,
    'menu' => true,
    'actions' => ['view', 'download'],
    'widgets' => [],
];
