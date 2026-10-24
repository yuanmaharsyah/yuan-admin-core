<?php

declare(strict_types=1);
use Yuan\AdminCore\Modules\AuditLog\Widgets\ActivityByDay;
use Yuan\AdminCore\Modules\AuditLog\Widgets\FailedLogins;

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
    'widgets' => [
        FailedLogins::class,
        ActivityByDay::class,
    ],
];
