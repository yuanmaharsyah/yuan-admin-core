<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\PHPStan\Fixtures\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Yuan\AdminCore\Data\LevelData;
use Yuan\AdminCore\Data\PaginatedData;
use Yuan\AdminCore\Permissions\Level;

final class PropsController
{
    /**
     * @param  PaginatedData<LevelData>  $levels
     */
    public function allowed(LevelData $level, PaginatedData $levels, ?LevelData $maybe): Response
    {
        return Inertia::render('Levels::Index', [
            'level' => $level,
            'levels' => $levels,
            'maybe' => $maybe,
            'canCreate' => true,
            'total' => 3,
            'lazy' => Inertia::defer(fn (): LevelData => $level),
            'optional' => Inertia::optional(fn (): ?LevelData => $maybe),
        ]);
    }

    public function dataAsProps(LevelData $level): Response
    {
        return Inertia::render('Levels::Show', $level);
    }

    /**
     * @param  list<array{id: int}>  $rows
     */
    public function arraysAndModels(Level $level, array $rows): Response
    {
        return Inertia::render('Levels::Index', [
            'level' => $level,
            'rows' => $rows,
            'names' => collect(['a']),
        ]);
    }

    public function helper(Level $level): Response
    {
        return inertia('Levels::Show', ['level' => $level]);
    }

    public function variable(Level $level): Response
    {
        $props = ['level' => $level];

        return Inertia::render('Levels::Show', $props);
    }

    public function wrapper(Level $level): Response
    {
        return Inertia::render('Levels::Show', [
            'level' => Inertia::defer(fn (): Level => $level),
        ]);
    }

    public function mixed(mixed $status): Response
    {
        return Inertia::render('Levels::Show', ['status' => $status]);
    }
}
