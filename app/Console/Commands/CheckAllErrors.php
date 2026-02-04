<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Services\Meetings\DriveMeetingService;
use App\Models\User;
use App\Models\MeetingContentContainer;
use App\Models\GoogleToken;
use Exception;

class CheckAllErrors extends Command
{
    protected $signature = 'test:all-errors {email}';
    protected $description = 'Check for all possible errors in the system';

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

            // Obtener containers
            $containers = MeetingContentContainer::all();
            $this->info("✅ Containers obtenidos: " . $containers->count());

            // Obtener google token usando username del juntifyUser
            $googleToken = null;
            if ($juntifyUser) {
                $googleToken = GoogleToken::where('username', $juntifyUser->username)->first();
            }
            $this->info("✅ Google token: " . ($googleToken ? 'Existe' : 'No existe'));

            // Verificar groupBy en meetings
            $meetingsByContainer = $meetings->groupBy('content_container_id');
            $this->info("✅ GroupBy funcionando correctamente");

            $this->info("✅ Stats disponibles: " . implode(', ', array_keys($stats)));
            $this->info("✅ Stats calculados: total=" . ($stats['total'] ?? 'N/A'));

            // Verificar si hay datos problemáticos
            foreach ($meetings as $index => $meeting) {
                if ($index >= 3) break; // Solo revisar los primeros 3

                $this->info("Reunión #{$meeting->id}:");
                $this->info("  - meeting_name: " . gettype($meeting->meeting_name) . " = " . $meeting->meeting_name);
                $this->info("  - started_at: " . gettype($meeting->started_at) . " = " . ($meeting->started_at ? $meeting->started_at->format('d/m/Y') : 'NULL'));
                $this->info("  - duration_minutes: " . gettype($meeting->duration_minutes) . " = " . ($meeting->duration_minutes ?? 'NULL'));
            }

            $this->info("🎉 Todas las verificaciones pasaron exitosamente");

        } catch (Exception $e) {
            $this->error("❌ Error encontrado: " . $e->getMessage());
            $this->error("❌ Línea: " . $e->getLine());
            $this->error("❌ Archivo: " . $e->getFile());
        }
    }
}
