<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\AuthService;
use App\Models\IntegrantesEmpresa;
use App\Models\Empresa;

class TestDduConnections extends Command
{
    protected $signature = 'ddu:test-connections {--email= : Email to test authentication}';
    protected $description = 'Test DDU database connections and authentication';

    public function handle()
    {
        $this->info('Testing DDU Database Connections...');
        $this->line('');

        // Test juntify connection
        $this->info('1. Testing juntify database connection...');
        try {
            $juntifyUserCount = DB::connection('juntify')->table('users')->count();
            $this->info("   ✓ Connected to juntify database. Found {$juntifyUserCount} users.");
        } catch (\Exception $e) {
            $this->error("   ✗ Failed to connect to juntify database: " . $e->getMessage());
            return 1;
        }

        // Test juntify_panels connection
        $this->info('2. Testing juntify_panels database connection...');
        try {
            $empresaCount = DB::connection('juntify_panels')->table('empresa')->count();
            $integrantesCount = DB::connection('juntify_panels')->table('integrantes_empresa')->count();
            $this->info("   ✓ Connected to juntify_panels database. Found {$empresaCount} empresas and {$integrantesCount} integrantes.");
        } catch (\Exception $e) {
            $this->error("   ✗ Failed to connect to juntify_panels database: " . $e->getMessage());
            return 1;
        }

        // Test DDU empresa existence
        $this->info('3. Checking DDU empresa...');
        try {
            $dduEmpresa = Empresa::where('nombre_empresa', 'DDU')->first();
            if ($dduEmpresa) {
                $this->info("   ✓ DDU empresa found with ID: {$dduEmpresa->id}");
            } else {
                $this->warn("   ! DDU empresa not found in database");
            }
        } catch (\Exception $e) {
            $this->error("   ✗ Error checking DDU empresa: " . $e->getMessage());
        }

        // Test DDU members
        $this->info('4. Checking DDU members...');
        try {
            $dduMembers = IntegrantesEmpresa::getAllDduMembersWithUsers();
            $this->info("   ✓ Found {$dduMembers->count()} DDU members:");

            foreach ($dduMembers->take(5) as $member) {
                $userInfo = $member->user_info ?? 'No user info';
                $email = is_object($userInfo) ? $userInfo->email : 'No email';
                $this->line("     - {$member->rol}: {$email}");
            }

            if ($dduMembers->count() > 5) {
                $this->line("     ... and " . ($dduMembers->count() - 5) . " more members");
            }
        } catch (\Exception $e) {
            $this->error("   ✗ Error checking DDU members: " . $e->getMessage());
        }

        // Test authentication if email provided
        if ($email = $this->option('email')) {
            $this->info("5. Testing authentication for email: {$email}");

            $authService = new AuthService();

            if ($authService->isDduUser($email)) {
                $this->info("   ✓ User {$email} is a valid DDU user");

                // You would need to provide password for full test
                $this->comment("   Note: Provide password to test full authentication");
            } else {
                $this->warn("   ! User {$email} is not a DDU user or doesn't exist");
            }
        }

        $this->line('');
        $this->info('Database connection test completed!');

        return 0;
    }
}
