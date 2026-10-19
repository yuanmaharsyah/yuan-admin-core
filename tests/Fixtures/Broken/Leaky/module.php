<?php

declare(strict_types=1);

// Breaks one contract rule per file, for the negative contract tests.
return [
    'name' => 'Leaky',
    'slug' => 'leaky',
    'label' => 'Leaky',
    'icon' => 'bug',
    'group' => 'master',
    'order' => 99,
    'enabled' => true,
    'core' => false,
    'superadmin_only' => false,
    'menu' => true,
    'actions' => ['view', 'approve'],
    'widgets' => [],
];
