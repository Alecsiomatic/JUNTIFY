<?php

namespace App\Http\Controllers;

use App\Services\Meetings\JuntifyMeetingService;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        // Obtener datos del usuario desde sesión Juntify
        $juntifyUser = Session::get('juntify_user', []);
        $juntifyCompany = Session::get('juntify_company', []);
        
        // Obtener rol del usuario
        $userRole = $juntifyCompany['rol_usuario'] ?? 'miembro';
        
        // Normalizar rol en español
        if (in_array(strtolower($userRole), ['admin', 'administrator'])) {
            $userRole = 'administrador';
        }

        // Obtener estadísticas básicas para el dashboard
        $stats = [
            'total_members' => 12, // Datos de ejemplo
            'recent_meetings' => 3, // Datos de ejemplo
            'pending_tasks' => 7,   // Datos de ejemplo
            'user_role' => $userRole
        ];

        return view('dashboard.index', compact('stats'));
    }

    /**
     * Reuniones index
     */
    public function reuniones(JuntifyMeetingService $juntifyMeetingService)
    {
        // Obtener datos del usuario desde sesión Juntify
        $juntifyUserData = Session::get('juntify_user');
        $userId = $juntifyUserData['id'] ?? null;
        $username = $juntifyUserData['username'] ?? null;

        if (!$userId) {
            return redirect()->route('login')->withErrors(['message' => 'Usuario no autenticado']);
        }

        // Crear objeto de usuario compatible con el servicio
        $user = (object)[
            'id' => $userId,
            'username' => $username,
            'email' => $juntifyUserData['email'] ?? null
        ];

        // Obtener reuniones desde Juntify API
        [$meetings, $stats] = $juntifyMeetingService->getOverviewForUser($user);

        // Obtener grupos del usuario desde Juntify API
        $userGroups = $juntifyMeetingService->getUserGroups($user);

        // Google Token no está disponible con API (se maneja en Juntify)
        $googleToken = null;

        return view('dashboard.reuniones.index', compact('stats', 'meetings', 'googleToken', 'userGroups'));
    }

}
