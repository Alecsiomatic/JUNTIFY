<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckPassword extends Command
{
    protected $signature = 'check:password {email} {password}';
    protected $description = 'Check if email and password combination is valid';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $this->info("Checking credentials for: {$email}");

        try {
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('email', $email)
                ->first();

            if (!$juntifyUser) {
                $this->error("❌ User not found");
                return 1;
            }

            $this->info("✓ User found: {$juntifyUser->full_name}");

            if (password_verify($password, $juntifyUser->password)) {
                $this->info("✅ Password is correct!");
            } else {
                $this->error("❌ Password is incorrect");
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
