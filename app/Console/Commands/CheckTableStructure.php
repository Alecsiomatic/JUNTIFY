<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckTableStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-table-structure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Estructura de user_panel_miembros:');

        $columns = \Illuminate\Support\Facades\DB::select('DESCRIBE user_panel_miembros');
        foreach ($columns as $col) {
            $this->line("   {$col->Field} - {$col->Type} - Default: {$col->Default}");
        }

        return 0;
    }
}
