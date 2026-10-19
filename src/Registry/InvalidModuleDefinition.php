<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Registry;

use RuntimeException;

final class InvalidModuleDefinition extends RuntimeException
{
    public static function rule(string $file, string $key, string $rule): self
    {
        return new self("Invalid module definition in [{$file}]: \"{$key}\" {$rule}.");
    }

    public static function duplicate(string $key, string $value, string $first, string $second): self
    {
        return new self("Duplicate module {$key} \"{$value}\" in [{$first}] and [{$second}]. Rename one, or turn off the core module with admin-core.modules.disabled.");
    }

    public static function coreDisabled(string $slug, string $file): self
    {
        return new self("Module \"{$slug}\" in [{$file}] is a core module ('core' => true) and cannot be listed in admin-core.modules.disabled.");
    }
}
