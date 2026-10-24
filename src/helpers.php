<?php

declare(strict_types=1);

use Yuan\AdminCore\Settings\Settings;

if (! function_exists('settings')) {
    /** A setting's stored value, or its declared default (spec §7.3). */
    function settings(string $key): mixed
    {
        return app(Settings::class)->get($key);
    }
}
