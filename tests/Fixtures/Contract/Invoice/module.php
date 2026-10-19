<?php

declare(strict_types=1);

return [
    'name' => 'Invoice',
    'slug' => 'invoices',
    'label' => 'Invoice',
    'icon' => 'receipt',
    'group' => 'keuangan',
    'order' => 20,
    'enabled' => true,
    'core' => false,
    'superadmin_only' => false,
    'menu' => true,
    'actions' => ['view', 'create', 'edit', 'delete', 'download', 'approve'],
    'widgets' => [],
];
