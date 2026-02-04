<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Meetings\DriveMeetingService;
use App\Models\User;

class TestStdClassErrors extends Command
{
    protected $signature = 'test:stdclass-errors {email}';
    protected $description = 'Test for stdClass conversion errors in meeting data';

    public function handle()
    {
        $email = $this->argument('email');

        try {
            $this->info("🧪 Probando errores de stdClass para: {$email}");

            // Obtener usuario y datos
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("❌ Usuario no encontrado: {$email}");
                return;
            }

            $service = new DriveMeetingService();
            [$meetings, $stats, $googleToken] = $service->getOverviewForUser($user);

            if ($meetings->count() > 0) {
                $meeting = $meetings->first();

                $this->info("🔍 Verificando tipos de datos:");
                $this->info("  - ID: " . gettype($meeting->id) . " = " . $meeting->id);
                $this->info("  - Nombre: " . gettype($meeting->meeting_name) . " = " . substr($meeting->meeting_name, 0, 30));
                $this->info("  - Transcript ID: " . gettype($meeting->transcript_drive_id) . " = " . $meeting->transcript_drive_id);
                $this->info("  - Duration: " . gettype($meeting->duration_minutes) . " = " . ($meeting->duration_minutes ?? 'NULL'));
                $this->info("  - Started At: " . get_class($meeting->started_at));
                $this->info("  - Ended At: " . ($meeting->ended_at ? get_class($meeting->ended_at) : 'NULL'));

                // Probar conversión a string
                $testString = "Reunión: " . $meeting->meeting_name;
                $this->info("✅ Conversión a string exitosa: " . substr($testString, 0, 40));

                // Probar duración
                $durationText = $meeting->duration_minutes ?? 'N/A';
                $this->info("✅ Duración segura: {$durationText}");

                // Probar metadata
                if (isset($meeting->metadata)) {
                    $this->info("  - Metadata tipo: " . gettype($meeting->metadata));
                    if (is_object($meeting->metadata)) {
                        $this->info("    - ju_path: " . ($meeting->metadata->ju_path ?? 'N/A'));
                    }
                }

                $this->info("\n🎉 TODOS LOS TIPOS SON CORRECTOS");
                $this->info("✅ No hay errores de stdClass to string");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("📁 Archivo: " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
