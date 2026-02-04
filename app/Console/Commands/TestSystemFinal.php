<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Services\Meetings\DriveMeetingService;
use App\Models\User;
use Exception;

class TestSystemFinal extends Command
{
    protected $signature = 'test:system-final {email}';
    protected $description = 'Final test of the complete system';

    public function handle()
    {
        $email = $this->argument('email');

        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->error('Usuario no encontrado');
                return;
            }

            Auth::login($user);
            $this->info("✅ Usuario autenticado: {$user->username}");

            // Obtener meetings usando el servicio
            $meetingService = new DriveMeetingService();
            [$meetings, $stats, $juntifyUser] = $meetingService->getOverviewForUser($user);

            $this->info("✅ Meetings obtenidas: " . $meetings->count());

            // Testear que las rutas se generen correctamente con IDs
            if ($meetings->count() > 0) {
                $meeting = $meetings->first();

                $this->info("Probando rutas con meeting ID: {$meeting->id}");
                $this->info("Tipo de ID: " . gettype($meeting->id));

                // Test route download.audio con ID
                $audioRoute = route('download.audio', $meeting->id);
                $this->info("✅ Route download.audio: " . $audioRoute);

                // Test route download.ju con ID
                $juRoute = route('download.ju', $meeting->id);
                $this->info("✅ Route download.ju: " . $juRoute);

                // Verificar tipos de datos
                $this->info("Meeting name: " . gettype($meeting->meeting_name) . " = " . $meeting->meeting_name);
                $this->info("Started at: " . ($meeting->started_at ? $meeting->started_at->format('d/m/Y H:i') : 'NULL'));
                $this->info("Audio Drive ID: " . ($meeting->audio_drive_id ?? 'NULL'));
                $this->info("Transcript Drive ID: " . ($meeting->transcript_drive_id ?? 'NULL'));
            }

            $this->info("🎉 Sistema funcionando correctamente sin errores de stdClass");

        } catch (Exception $e) {
            $this->error("❌ Error encontrado: " . $e->getMessage());
            $this->error("❌ Línea: " . $e->getLine());
            $this->error("❌ Archivo: " . $e->getFile());
        }
    }
}
