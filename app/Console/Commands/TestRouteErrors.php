<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Services\Meetings\DriveMeetingService;
use App\Models\User;
use App\Models\MeetingContentContainer;
use App\Models\GoogleToken;
use Exception;

class TestRouteErrors extends Command
{
    protected $signature = 'test:route-errors {email}';
    protected $description = 'Test if route errors are fixed';

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

            // Testear las rutas problemáticas
            if ($meetings->count() > 0) {
                $meeting = $meetings->first();

                $this->info("Probando rutas con meeting ID: {$meeting->id}");

                // Test route download.audio
                try {
                    $audioRoute = route('download.audio', $meeting->id);
                    $this->info("✅ Route download.audio: " . $audioRoute);
                } catch (Exception $e) {
                    $this->error("❌ Error en download.audio: " . $e->getMessage());
                }

                // Test route download.ju
                try {
                    $juRoute = route('download.ju', $meeting->id);
                    $this->info("✅ Route download.ju: " . $juRoute);
                } catch (Exception $e) {
                    $this->error("❌ Error en download.ju: " . $e->getMessage());
                }

                // Test object type
                $this->info("Tipo de meeting: " . gettype($meeting));
                $this->info("Tipo de meeting->id: " . gettype($meeting->id));
                $this->info("Valor de meeting->id: {$meeting->id}");

                // Verificar que no podamos pasar el objeto completo
                try {
                    $badRoute = route('download.audio', $meeting);
                    $this->error("❌ PROBLEMA: Aún se puede pasar el objeto completo");
                } catch (Exception $e) {
                    $this->info("✅ CORRECTO: Ya no se puede pasar el objeto completo - " . $e->getMessage());
                }
            }

            $this->info("🎉 Pruebas de rutas completadas");

        } catch (Exception $e) {
            $this->error("❌ Error encontrado: " . $e->getMessage());
            $this->error("❌ Línea: " . $e->getLine());
            $this->error("❌ Archivo: " . $e->getFile());
        }
    }
}
