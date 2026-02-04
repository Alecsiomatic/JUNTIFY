<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DescribeJuntifyTable extends Command
{
    protected $signature = 'juntify:describe {table}';
    protected $description = 'Describe structure of a table in juntify database';

    public function handle()
    {
        $table = $this->argument('table');

        try {
            $structure = DB::connection('juntify')->select("DESCRIBE {$table}");

            $this->info("📋 Estructura de la tabla: {$table}");
            $this->info('═══════════════════════════════════════════════════════════════════');

            foreach ($structure as $column) {
                $this->line(sprintf(
                    "  • %-20s %-20s %s %s %s",
                    $column->Field,
                    $column->Type,
                    $column->Null === 'YES' ? 'NULL' : 'NOT NULL',
                    $column->Key ? "KEY: {$column->Key}" : '',
                    $column->Default !== null ? "DEFAULT: {$column->Default}" : ''
                ));
            }

            // También mostrar algunos registros de ejemplo
            $this->info("\n🔍 Registros de ejemplo (últimos 3):");
            $this->info('═══════════════════════════════════════════════════════════════════');

            $samples = DB::connection('juntify')->table($table)->orderByDesc('id')->limit(3)->get();

            if ($samples->count() > 0) {
                foreach ($samples as $sample) {
                    $this->line("ID: " . ($sample->id ?? 'N/A'));
                    foreach ($sample as $key => $value) {
                        if ($key !== 'id') {
                            $displayValue = is_string($value) && strlen($value) > 50 ?
                                substr($value, 0, 50) . '...' : $value;
                            $this->line("  {$key}: " . ($displayValue ?? 'NULL'));
                        }
                    }
                    $this->line('---');
                }
            } else {
                $this->line("No hay registros en la tabla.");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}
