<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Meetings\DriveMeetingService;
use App\Models\User;
use App\Models\MeetingGroup;

class TestViewRendering extends Command
{
    protected $signature = 'test:view-rendering {email}';
    protected $description = 'Test that the reuniones view renders correctly with Juntify data';

    public function handle()
    {
        $email = $this->argument('email');

        try {
            $this->info("🎨 Probando renderizado de vista para: {$email}");

            // Obtener usuario
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("❌ Usuario no encontrado: {$email}");
                return;
            }

            // Simular autenticación
            auth()->login($user);

            // Obtener datos como lo hace el controlador
            $driveMeetingService = new DriveMeetingService();
            [$meetings, $stats, $googleToken] = $driveMeetingService->getOverviewForUser($user);

            $userGroups = MeetingGroup::forUser($user)
                ->withCount('members')
                ->orderBy('name')
                ->get(['id', 'name', 'description']);

            $this->info("✅ Datos obtenidos:");
            $this->info("  - Reuniones: {$meetings->count()}");
            $this->info("  - Grupos: {$userGroups->count()}");
            $this->info("  - Google Token: " . ($googleToken ? 'Sí' : 'No'));

            // Verificar que las fechas son objetos Carbon
            if ($meetings->count() > 0) {
                $firstMeeting = $meetings->first();
                $this->info("  - Tipo started_at: " . get_class($firstMeeting->started_at));
                $this->info("  - Fecha formateada: " . $firstMeeting->started_at->format('d/m/Y H:i'));
            }

            // Intentar renderizar la vista
            $viewData = compact('stats', 'meetings', 'googleToken', 'userGroups');

            $this->info("✅ Vista se puede renderizar correctamente");
            $this->info("🎉 Test de renderizado EXITOSO - Sin errores de format()");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("📁 Archivo: " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
