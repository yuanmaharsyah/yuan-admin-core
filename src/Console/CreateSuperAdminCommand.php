<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Yuan\AdminCore\Permissions\Level;

/**
 * Recovery path for a lost Super Admin (spec §6.3). The password is always
 * asked for, never taken from an option, so it stays out of shell history.
 */
final class CreateSuperAdminCommand extends Command
{
    protected $signature = 'admin:create-superadmin {--name= : Display name} {--username= : Login username} {--email= : Email address}';

    protected $description = 'Create an active Super Admin account';

    public function handle(): int
    {
        $input = [
            'name' => $this->option('name') ?? $this->ask('Name'),
            'username' => $this->option('username') ?? $this->ask('Username'),
            'email' => $this->option('email') ?? $this->ask('Email'),
            'password' => $this->secret('Password'),
        ];

        /** @var class-string<Model> $model */
        $model = config()->string('auth.providers.users.model');
        $table = (new $model)->getTable();
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', "unique:{$table},username"],
            'email' => ['required', 'email', 'max:255', "unique:{$table},email"],
            'password' => ['required', 'string', Password::min(10)->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = new $model;

        if (! method_exists($user, 'assignLevel')) {
            $this->components->error("The user model [{$model}] must use the HasLevel trait.");

            return self::FAILURE;
        }

        $user->forceFill([...$validator->validated(), 'is_active' => true])->save();
        $user->assignLevel(Level::superAdmin());

        $this->components->info("Super Admin [{$input['username']}] created.");

        return self::SUCCESS;
    }
}
