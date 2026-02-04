<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\IntegrantesEmpresa;
use App\Models\Empresa;

class TestDduData extends Command
{
    protected $signature = 'test:ddu-data {--email=}';
    protected $description = 'Test DDU data and membership';

    public function handle()
    {
        $this->info('Testing DDU data...');

        // Check empresas
        $this->info('Empresas in juntify_panels:');
        $empresas = Empresa::all();
        foreach ($empresas as $empresa) {
            $this->line("  - ID: {$empresa->id}, Name: {$empresa->nombre_empresa}, User: {$empresa->iduser}");
        }

        // Check integrantes
        $this->info('Integrantes in juntify_panels:');
        $integrantes = IntegrantesEmpresa::with('empresa')->get();
        foreach ($integrantes as $integrante) {
            $empresaName = $integrante->empresa ? $integrante->empresa->nombre_empresa : 'No empresa';
            $this->line("  - User ID: {$integrante->iduser}, Empresa: {$empresaName}, Rol: {$integrante->rol}");
        }

        // Test specific email if provided
        if ($email = $this->option('email')) {
            $this->info("Testing email: {$email}");

            // Check if user exists in juntify
            $juntifyUser = DB::connection('juntify')->table('users')->where('email', $email)->first();
            if ($juntifyUser) {
                $this->info("  ✓ User found in juntify: ID {$juntifyUser->id}");

                // Check DDU membership
                $isDdu = IntegrantesEmpresa::isDduMember($juntifyUser->id);
                $this->info($isDdu ? "  ✓ User IS a DDU member" : "  ✗ User is NOT a DDU member");

            } else {
                $this->error("  ✗ User not found in juntify");
            }
        }

        return 0;
    }
}
