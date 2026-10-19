<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Registry;

final readonly class MenuItem
{
    public function __construct(
        public string $slug,
        public string $label,
        public string $icon,
        public string $href,
    ) {}
}
