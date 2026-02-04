<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IntegrantesEmpresa;
use Illuminate\Support\Facades\DB;

class TestDduSystem extends Command
{
    protected $signature = 'test:ddu-system {email}';
    protected $description = 'Test DDU authentication system';

    public function handle()
    {
        $email = $this->argument('email');

        try {
            // Buscar usuario en juntify
            $userId = \DB::connection('juntify')
                ->table('users')
                ->where('email', $email)
                ->value('id');

            if ($userId) {
                $this->info("✅ Usuario encontrado en Juntify: {$email} (ID: {$userId})");

                // Probar método estático
                $isDdu = IntegrantesEmpresa::isDduMember($userId);
                $this->info("   Método isDduMember: " . ($isDdu ? "✅ Sí" : "❌ No"));

                if ($isDdu) {
                    $membership = IntegrantesEmpresa::getDduMembership($userId);
                    $this->info("   Detalles: ID {$membership->id}, Rol: {$membership->rol}");
                    $this->info("   Empresa: {$membership->empresa->nombre_empresa}");
                } else {
                    $this->error("❌ Usuario no es miembro DDU");
                }
            } else {
                $this->error("❌ Usuario no encontrado en Juntify: {$email}");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("   Archivo: " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
