<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteNurseryAdmin extends Command
{
    protected $signature = 'nursery:make-admin {email : The email address of the registered account}';

    protected $description = 'Grant administrator access to an existing nursery user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No registered user was found with that email address.');

            return self::FAILURE;
        }

        $user->role = 'admin';
        $user->save();

        $this->info("Administrator access granted to {$user->email}.");

        return self::SUCCESS;
    }
}
