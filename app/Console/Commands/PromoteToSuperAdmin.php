<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteToSuperAdmin extends Command
{
    protected $signature = 'admin:promote-super {email : Email of the existing admin/student to promote}';
    protected $description = 'One-time bootstrap: promotes an existing user to super_admin. Only needed once per environment.';

    public function handle()
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('No user found with that email.');
            return 1;
        }

        $user->update(['role' => 'super_admin']);
        $this->info("{$user->email} is now a super admin.");
        return 0;
    }
}