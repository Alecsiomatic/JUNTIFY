<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\JuntifyMeetingController;
use Illuminate\Http\Request;
use App\Models\User;

class TestMeetingDetails extends Command
{
    protected $signature = 'test:meeting-details {email} {meetingId}';
    protected $description = 'Test meeting details endpoint';

    public function handle()
    {
        $email = $this->argument('email');
        $meetingId = $this->argument('meetingId');

        try {
            // Simular usuario autenticado
            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->error("❌ Usuario no encontrado: {$email}");
                return;
            }

            $this->info("✅ Usuario: {$user->full_name}");

            // Simular autenticación
            auth()->login($user);

            // Probar el controlador
            $controller = new JuntifyMeetingController();
            $response = $controller->showDetails($meetingId);
            $data = json_decode($response->getContent(), true);

            $this->info("🔍 Respuesta del controlador:");
            $this->info("  Status: " . $response->getStatusCode());

            if (isset($data['error'])) {
                $this->error("❌ Error: " . $data['error']);
            } else {
                $this->info("✅ Datos obtenidos correctamente");
                $this->info("  Resumen: " . ($data['summary'] ?? 'N/A'));
                $this->info("  Puntos clave: " . count($data['key_points'] ?? []));
                $this->info("  Segmentos: " . count($data['segments'] ?? []));

                if (isset($data['meeting'])) {
                    $this->info("  Reunión: " . $data['meeting']['name']);
                    $this->info("  ID: " . $data['meeting']['id']);
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("📁 Archivo: " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
