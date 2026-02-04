<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Meetings\DriveMeetingService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TestMeetingService extends Command
{
    protected $signature = 'test:meeting-service {email}';
    protected $description = 'Test the DriveMeetingService with a user email';

    public function handle()
    {
        $email = $this->argument('email');

        try {
            // Buscar usuario local (DDU)
            $localUser = User::where('email', $email)->first();

            if (!$localUser) {
                $this->error("❌ Usuario local no encontrado: {$email}");
                return;
            }

            $this->info("✅ Usuario local encontrado: {$localUser->full_name}");
            $this->info("🆔 ID Local: {$localUser->id}");

            // Probar el servicio
            $service = new DriveMeetingService();
            [$meetings, $stats, $googleToken] = $service->getOverviewForUser($localUser);

            $this->info("\n📊 Estadísticas:");
            $this->info("  Total: {$stats['total']}");
            $this->info("  Finalizadas: {$stats['finalizadas']}");
            $this->info("  Esta semana: {$stats['esta_semana']}");

            $this->info("\n📅 Reuniones encontradas: " . $meetings->count());

            if ($meetings->count() > 0) {
                $this->info("─────────────────────────────────────────");
                foreach ($meetings->take(5) as $meeting) {
                    $this->line("🔸 ID: {$meeting->id}");
                    $this->line("  📝 Nombre: {$meeting->meeting_name}");
                    $this->line("  📅 Fecha: {$meeting->created_at}");
                    $this->line("  🎵 Audio: " . ($meeting->audio_drive_id ? '✅' : '❌'));
                    $this->line("  📄 Transcript: " . ($meeting->transcript_drive_id ? '✅' : '❌'));
                    $this->line("---");
                }

                if ($meetings->count() > 5) {
                    $this->line("... y " . ($meetings->count() - 5) . " reuniones más.");
                }
            }

            $this->info("\n🔗 Google Token: " . ($googleToken ? '✅ Disponible' : '❌ No disponible'));

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("📁 Archivo: " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
