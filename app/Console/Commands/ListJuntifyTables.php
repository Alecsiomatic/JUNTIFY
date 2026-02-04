<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListJuntifyTables extends Command
{
    protected $signature = 'juntify:list-tables';
    protected $description = 'List all tables in juntify database';

    public function handle()
    {
        try {
            $tables = DB::connection('juntify')->select('SHOW TABLES');

            $this->info('📋 Tablas en la base de datos juntify:');
            $this->info('═══════════════════════════════════════');

            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                $this->line("  • {$tableName}");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}
