<?php

namespace Database\Seeders;

use App\Services\AdminProvisioner;
use Illuminate\Database\Seeder;

/**
 * Creates the first administrator from ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD in .env.
 * Skipped (with a warning) when those are not set, so no default password ever exists.
 * Remove the ADMIN_* lines from .env after the first run.
 */
class AdminUserSeeder extends Seeder
{
    public function run(AdminProvisioner $provisioner): void
    {
        $email = config('arsh.admin.email');
        $password = config('arsh.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('Admin not created: set ADMIN_EMAIL and ADMIN_PASSWORD in .env, or run `php artisan arsh:create-admin`.');

            return;
        }

        $user = $provisioner->createOrPromote((string) config('arsh.admin.name') ?: 'Administrator', (string) $email, (string) $password);
        $this->command?->info("Administrator ready: {$user->email}");
    }
}
