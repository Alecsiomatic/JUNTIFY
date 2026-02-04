<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Services\Meetings\DriveMeetingService;
use App\Models\User;
use Exception;

class TestMeetingIdFix extends Command
{
    protected $signature = 'test:meeting-id-fix {email}';
    protected $description = 'Test if meeting_id error is fixed';

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

            // Verificar propiedades disponibles en el primer meeting
            if ($meetings->count() > 0) {
                $meeting = $meetings->first();

                $this->info("Verificando propiedades del meeting:");
                $this->info("- meeting->id: " . (isset($meeting->id) ? "✅ {$meeting->id}" : "❌ NO EXISTE"));
                $this->info("- meeting->meeting_id: " . (isset($meeting->meeting_id) ? "⚠️ {$meeting->meeting_id}" : "✅ NO EXISTE (correcto)"));
                $this->info("- meeting->meeting_name: " . (isset($meeting->meeting_name) ? "✅ {$meeting->meeting_name}" : "❌ NO EXISTE"));

                // Test substr con id
                try {
                    $shortId = substr($meeting->id, 0, 8);
                    $this->info("✅ substr(\$meeting->id, 0, 8): {$shortId}");
                } catch (Exception $e) {
                    $this->error("❌ Error con substr(\$meeting->id): " . $e->getMessage());
                }

                // Test que meeting_id no existe
                try {
                    $badAccess = $meeting->meeting_id;
                    $this->error("❌ PROBLEMA: \$meeting->meeting_id todavía es accesible: {$badAccess}");
                } catch (Exception $e) {
                    $this->info("✅ CORRECTO: \$meeting->meeting_id no es accesible (como esperado)");
                }

                // Mostrar todas las propiedades disponibles
                $this->info("Propiedades disponibles en el objeto meeting:");
                $properties = get_object_vars($meeting);
                foreach ($properties as $key => $value) {
                    $type = gettype($value);
                    $displayValue = $type === 'string' ? $value : $type;
                    $this->info("  - {$key}: {$displayValue}");
                }
            }

            $this->info("🎉 Verificación de meeting_id completada");

        } catch (Exception $e) {
            $this->error("❌ Error encontrado: " . $e->getMessage());
            $this->error("❌ Línea: " . $e->getLine());
            $this->error("❌ Archivo: " . $e->getFile());
        }
    }
}
