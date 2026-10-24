<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Generator;

/** The module a `foreign(table)` field points at: its model and slug (whose `lookup` route feeds the RelationSelect). */
final readonly class ForeignTarget
{
    /**
     * @param  class-string  $model
     */
    public function __construct(
        public string $model,
        public string $slug,
    ) {}

    public function shortName(): string
    {
        return class_basename($this->model);
    }
}
