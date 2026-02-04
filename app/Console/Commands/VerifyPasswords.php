<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifyPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify password hashes for DDU users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando hashes de contraseñas DDU...');

        $dduEmails = [
            'ddujuntify@gmail.com',
            'ddujuntify1@gmail.com',
            'ddujuntify2@gmail.com'
        ];

        foreach ($dduEmails as $email) {
            $user = \App\Models\User::where('email', $email)->first();

            if ($user) {
                $hashType = substr($user->password, 0, 4);
                $hashLength = strlen($user->password);

                $this->line("📧 {$email}");
                $this->line("   Hash: " . substr($user->password, 0, 30) . "...");
                $this->line("   Tipo: {$hashType} (Length: {$hashLength})");

                // Probar verificación con Pass_123456
                $verified = password_verify('Pass_123456', $user->password);
                $this->line("   Verificación 'Pass_123456': " . ($verified ? '✅ OK' : '❌ FAIL'));
                $this->line('');
            } else {
                $this->warn("❌ Usuario no encontrado: {$email}");
            }
        }

        return 0;
    }
}
