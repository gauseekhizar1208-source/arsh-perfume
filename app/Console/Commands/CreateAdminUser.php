<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\AdminProvisioner;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CreateAdminUser extends Command
{
    protected $signature = 'arsh:create-admin {email? : Administrator email} {--name= : Display name}';

    protected $description = 'Create (or promote) the administrator account. The password is typed interactively and never stored in shell history.';

    public function handle(AdminProvisioner $provisioner): int
    {
        if (! Role::query()->where('name', Role::ADMIN)->exists()) {
            $this->error('Roles are missing. Run `php artisan migrate --seed` (or db:seed --class=RoleSeeder) first.');

            return self::FAILURE;
        }

        $email = $this->argument('email') ?: $this->ask('Administrator email');
        $name = $this->option('name') ?: $this->ask('Display name', 'Administrator');
        $password = config('arsh.admin.password') ?: $this->secret('Password (min '.AdminProvisioner::MIN_PASSWORD_LENGTH.' characters)');

        if (! config('arsh.admin.password') && $password !== $this->secret('Repeat password')) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        try {
            $user = $provisioner->createOrPromote((string) $name, (string) $email, (string) $password);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Administrator ready: {$user->email}");

        return self::SUCCESS;
    }
}
