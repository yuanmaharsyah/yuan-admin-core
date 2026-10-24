<?php

declare(strict_types=1);
use Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Widgets\Duplicate;
use Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Widgets\Misnamed;
use Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Widgets\NotAWidget;

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
    'widgets' => [
        Misnamed::class,
        NotAWidget::class,
        Duplicate::class,
    ],
];
