<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestLogin extends Command
{
    protected $signature = 'test:login {email}';
    protected $description = 'Test login process without actually logging in';

    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Testing login process for: {$email}");

        try {
            // Test the same logic as LoginRequest
            $juntifyUser = \DB::connection('juntify')
                ->table('users')
                ->where('email', $email)
                ->first();

            if (!$juntifyUser) {
                $this->error("❌ User not found in juntify");
                return 1;
            }

            $this->info("✓ User found in juntify: {$juntifyUser->full_name}");

            // Test DDU membership
            $isDduMember = \App\Models\IntegrantesEmpresa::isDduMember($juntifyUser->id);

            if ($isDduMember) {
                $this->info("✅ User IS a DDU member - Login would succeed");

                // Get membership details
                $membership = \App\Models\IntegrantesEmpresa::getDduMembership($juntifyUser->id);
                if ($membership) {
                    $this->table(['Field', 'Value'], [
                        ['Role', $membership->rol],
                        ['Permissions', json_encode($membership->permisos)],
                        ['Empresa ID', $membership->empresa_id],
                    ]);
                }
            } else {
                $this->error("❌ User is NOT a DDU member - Login would fail with DDU access error");
            }

        } catch (\Exception $e) {
            $this->error("Error during test: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
