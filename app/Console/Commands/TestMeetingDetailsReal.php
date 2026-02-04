<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\JuntifyMeetingController;

class TestMeetingDetailsReal extends Command
{
    protected $signature = 'test:meeting-details-real {email}';
    protected $description = 'Test meeting details with real data';

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

            // Llamar directamente al controlador
            $controller = new JuntifyMeetingController();
            $response = $controller->showDetails(89); // Usando meeting ID 89 que sabemos que tiene tareas
            
            $data = $response->getData(true);
            
            if (isset($data['error'])) {
                $this->error("❌ Error: " . $data['error']);
                return;
            }
            
            $this->info("✅ Response exitosa del controlador");
            $this->info("📋 Summary: " . $data['summary']);
            
            $this->info("🔑 Key Points (" . count($data['key_points']) . "):");
            foreach ($data['key_points'] as $index => $point) {
                $this->info("  " . ($index + 1) . ". " . substr($point, 0, 100) . (strlen($point) > 100 ? "..." : ""));
            }
            
            if (isset($data['tasks']) && count($data['tasks']) > 0) {
                $this->info("📝 Tasks (" . count($data['tasks']) . "):");
                foreach ($data['tasks'] as $task) {
                    $this->info("  • " . substr($task['task'], 0, 80) . (strlen($task['task']) > 80 ? "..." : ""));
                    $this->info("    Asignado a: " . ($task['assigned_to'] ?? 'No asignado'));
                }
            } else {
                $this->info("📝 No hay tareas disponibles");
            }
            
            $this->info("🎵 Audio URL: " . ($data['audio_url'] ? 'Disponible' : 'No disponible'));
            $this->info("📊 Segments: " . count($data['segments']) . " segmentos");
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("❌ Línea: " . $e->getLine());
            $this->error("❌ Archivo: " . $e->getFile());
        }
    }
}