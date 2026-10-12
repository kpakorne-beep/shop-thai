<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {email : Email address for the administrator}';

    protected $description = 'Create or promote a user account to shop administrator';

    public function handle()
    {
        $email = strtolower(trim($this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid email address is required.');

            return 1;
        }

        $password = $this->secret('Password (minimum 12 characters)');
        if (! is_string($password) || strlen($password) < 12) {
            $this->error('The password must be at least 12 characters.');

            return 1;
        }

        $confirmation = $this->secret('Confirm password');
        if (! is_string($confirmation) || ! hash_equals($password, $confirmation)) {
            $this->error('Password confirmation does not match.');

            return 1;
        }

        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $name = $this->ask('Administrator name');
            if (! is_string($name) || trim($name) === '') {
                $this->error('A name is required for a new administrator.');

                return 1;
            }

            $user->name = trim($name);
        }

        $user->password = Hash::make($password);
        $user->is_admin = true;
        $user->save();

        $this->info("Administrator account is ready for {$email}.");

        return 0;
    }
}
