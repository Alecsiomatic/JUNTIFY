<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AuthService;

class TestAuthService extends Command
{
    protected $signature = 'test:auth {email} {password?}';
    protected $description = 'Test AuthService validation';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password') ?? 'testpassword';

        $authService = new AuthService();

        $this->info("Testing AuthService for: {$email}");

        // Test isDduUser
        $isDdu = $authService->isDduUser($email);
        $this->info($isDdu ? "✓ User IS DDU" : "✗ User is NOT DDU");

        // Test validateDduUser (without real password for security)
        if ($this->confirm('Test with password validation?')) {
            $result = $authService->validateDduUser($email, $password);

            if ($result) {
                $this->info("✓ Validation successful!");
                $this->table(['Field', 'Value'], [
                    ['ID', $result['id']],
                    ['Username', $result['username']],
                    ['Email', $result['email']],
                    ['DDU Role', $result['ddu_role']],
                ]);
            } else {
                $this->error("✗ Validation failed - could be password or DDU membership");
            }
        }

        return 0;
    }
}
