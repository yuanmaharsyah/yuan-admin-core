<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Generator;

use LogicException;

/**
 * Renders a stub from `stubs/`: each `{{ key }}` is replaced by its value
 * (plain str_replace, no template engine); a placeholder left over is a bug.
 */
final class StubRenderer
{
    public const string STUBS = __DIR__.'/../../stubs';

    /**
     * @param  array<string, string>  $values
     */
    public static function render(string $stub, array $values): string
    {
        $path = self::STUBS."/{$stub}.stub";
        $template = is_file($path) ? (string) file_get_contents($path) : throw new LogicException("Missing stub [{$stub}].");
        $rendered = str_replace(array_map(fn (string $key): string => "{{ {$key} }}", array_keys($values)), array_values($values), $template);

        if (preg_match('/\{\{ ([A-Za-z_]+) \}\}/', $rendered, $left) === 1) {
            throw new LogicException("Stub [{$stub}] has no value for {{ {$left[1]} }}.");
        }

        return $rendered;
    }
}
