<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use Illuminate\Console\Command;
use Yuan\AdminCore\Settings\Settings;

/** The CLI escape hatch for Settings → Akses Situs; audited like a save in Settings (channel `cli`). */
final class AccessCommand extends Command
{
    protected $signature = 'admin:access {mode : open, maintenance or coming_soon} {--message= : The text on the public page}';

    protected $description = 'Set the site access mode for public pages';

    public function handle(Settings $settings): int
    {
        $mode = $this->argument('mode');
        $options = $settings->definition('akses.mode')->options;

        if (! is_string($mode) || ! array_key_exists($mode, $options)) {
            $this->components->error('Mode must be one of: '.implode(', ', array_keys($options)).'.');

            return self::FAILURE;
        }

        $input = ['mode' => $mode];

        if (is_string($message = $this->option('message'))) {
            $input['pesan'] = $message;
        }

        $settings->save('akses', $input);
        $this->components->info("Site access: {$options[$mode]}.");

        return self::SUCCESS;
    }
}
