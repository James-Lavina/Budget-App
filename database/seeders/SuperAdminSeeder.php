<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the first super admin from ADMIN_EMAIL / ADMIN_PASSWORD in .env.
 *
 * No credentials are hard-coded: a public repository with a default admin
 * password would be an open door on every deployment. If the variables are
 * missing the seeder skips and tells you how to fix it.
 */
class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $config   = config('budgetwise.admin');
        $name     = trim((string) ($config['name'] ?? '')) ?: 'Super Admin';
        $email    = trim((string) ($config['email'] ?? ''));
        $password = (string) ($config['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->say('warn', 'Skipped super admin: set ADMIN_EMAIL and ADMIN_PASSWORD in .env, then run `php artisan db:seed --class=SuperAdminSeeder`.');

            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->say('error', "Skipped super admin: ADMIN_EMAIL \"{$email}\" is not a valid email address.");

            return;
        }

        if (strlen($password) < 8) {
            $this->say('error', 'Skipped super admin: ADMIN_PASSWORD must be at least 8 characters.');

            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->say('info', "A user with {$email} already exists. Left unchanged.");

            return;
        }

        User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => 'super_admin',
            'status'   => 'active',
        ]);

        $this->say('info', "Created super admin {$email}. Change the password after your first login.");
    }

    /** $this->command is null when a seeder runs from a test, so guard it. */
    private function say(string $level, string $message): void
    {
        if ($this->command) {
            $this->command->{$level}($message);
        }
    }
}