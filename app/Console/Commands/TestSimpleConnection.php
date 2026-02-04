<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\IntegrantesEmpresa;

class TestSimpleConnection extends Command
{
    protected $signature = 'test:db-simple';
    protected $description = 'Simple database connection test';

    public function handle()
    {
        $this->info('Testing simple database connections...');

        try {
            // Test default connection
            $this->info('Testing default connection...');
            $count = DB::table('users')->count();
            $this->info("Default DB users: {$count}");

        } catch (\Exception $e) {
            $this->error('Default connection failed: ' . $e->getMessage());
        }

        try {
            // Test juntify_panels connection
            $this->info('Testing juntify_panels connection...');
            $empresas = DB::connection('juntify_panels')->table('empresa')->count();
            $this->info("Juntify_panels empresas: {$empresas}");

        } catch (\Exception $e) {
            $this->error('Juntify_panels connection failed: ' . $e->getMessage());
        }

        try {
            // Test IntegrantesEmpresa model
            $this->info('Testing IntegrantesEmpresa model...');
            $integrantes = IntegrantesEmpresa::count();
            $this->info("Total integrantes: {$integrantes}");

        } catch (\Exception $e) {
            $this->error('IntegrantesEmpresa model failed: ' . $e->getMessage());
        }

        return 0;
    }
}
