<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetTestPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:set-password {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a test password for a user (creates bcrypt hash)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ Usuario no encontrado: {$email}");
            return 1;
        }

        // Simular hash bcryptjs ($2b$) - convertir $2y$ a $2b$
        $phpHash = password_hash($password, PASSWORD_BCRYPT);
        $bcryptjsHash = str_replace('$2y$', '$2b$', $phpHash);

        $user->password = $bcryptjsHash;
        $user->save();

        $this->info("✅ Contraseña actualizada para: {$email}");
        $this->line("   Hash bcryptjs ($2b$): " . substr($bcryptjsHash, 0, 25) . "...");

        // Verificar que funcione con password_verify (compatible)
        $verification = password_verify($password, $bcryptjsHash);
        $this->line("   Verificación PHP: " . ($verification ? 'OK' : 'FALLO'));

        return 0;
    }
}
