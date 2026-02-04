<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\IntegrantesEmpresa;
use App\Models\Empresa;

class CreateTestUser extends Command
{
    protected $signature = 'test:create-user {email} {password}';
    protected $description = 'Create a test DDU user with known password';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        // Create user in juntify database
        $userId = \Illuminate\Support\Str::uuid();

        try {
            // Insert user in juntify database
            DB::connection('juntify')->table('users')->insert([
                'id' => $userId,
                'username' => explode('@', $email)[0],
                'full_name' => 'Test DDU User',
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'current_organization_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("✓ User created in juntify database: {$email}");

            // Get or create DDU empresa
            $dduEmpresa = Empresa::where('nombre_empresa', 'DDU')->first();

            if (!$dduEmpresa) {
                $this->warn("DDU empresa not found. Creating one...");
                $dduEmpresa = Empresa::create([
                    'iduser' => $userId,
                    'nombre_empresa' => 'DDU',
                    'rol' => 'founder',
                    'es_administrador' => true,
                ]);
            }

            // Add user to DDU empresa
            IntegrantesEmpresa::create([
                'iduser' => $userId,
                'empresa_id' => $dduEmpresa->id,
                'rol' => 'administrador',
                'permisos' => json_encode(['admin']),
            ]);

            $this->info("✓ User added to DDU empresa");
            $this->info("Test credentials:");
            $this->line("Email: {$email}");
            $this->line("Password: {$password}");

        } catch (\Exception $e) {
            $this->error("Error creating test user: " . $e->getMessage());
        }

        return 0;
    }
}
