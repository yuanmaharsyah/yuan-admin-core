<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Registry;

final readonly class MenuGroup
{
    /**
     * @param  list<MenuItem>  $items
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public array $items,
    ) {}
}
