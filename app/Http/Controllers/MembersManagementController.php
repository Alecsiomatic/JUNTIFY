<?php

namespace App\Http\Controllers;

use App\Services\JuntifyApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class MembersManagementController extends Controller
{
    private JuntifyApiService $juntifyApi;

    public function __construct(JuntifyApiService $juntifyApi)
    {
        $this->juntifyApi = $juntifyApi;
    }

    /**
     * Mostrar página de gestión de miembros
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        
        // Obtener miembros de DDU desde el endpoint de Juntify
        $membersResult = $this->juntifyApi->getCompanyMembers(env('DDU_EMPRESA_ID'));
        
        $members = [];
        $existingUserIds = [];
        $stats = [
            'total' => 0,
            'active' => 0,
            'admins' => 0,
            'collaborators' => 0
        ];

        if ($membersResult['success']) {
            $data = $membersResult['data'];
            
            // Convertir los miembros al formato esperado por la vista
            foreach ($data['members'] as $member) {
                $members[] = (object)[
                    'id' => $member['id'],
                    'email' => $member['email'],
                    'name' => $member['name'],
                    'role' => $member['is_owner'] ? 'Dueño' : ($member['rol'] ?? 'miembro'),
                    'is_owner' => $member['is_owner'],
                    'fecha_agregado' => $member['fecha_agregado']
                ];
                $existingUserIds[] = $member['id'];
            }
            
            // Usar las estadísticas del endpoint si están disponibles
            if (isset($data['stats'])) {
                $stats = [
                    'total' => $data['stats']['total_members'] ?? count($members),
                    'active' => $data['stats']['active'] ?? count($members),
                    'admins' => $data['stats']['admins'] ?? 0,
                    'collaborators' => $data['stats']['members'] ?? 0
                ];
            }
        } else {
            Log::warning('No se pudieron obtener miembros de DDU: ' . ($membersResult['error'] ?? 'Error desconocido'));
        }

        // Obtener contactos del usuario autenticado (dueño de DDU)
        $contacts = [];
        $userId = Session::get('user_id'); // O desde el usuario autenticado
        
        if ($userId) {
            $contactsResult = $this->juntifyApi->getUserContacts($userId, env('DDU_EMPRESA_ID'));
            
            if ($contactsResult['success']) {
                $contacts = $contactsResult['data']['contacts'] ?? [];
            }
        }

        // Solo buscar usuarios en Juntify si hay un término de búsqueda
        $availableUsers = [];
        if ($search && !empty(trim($search))) {
            $result = $this->juntifyApi->getUsersList($search, null);

            if (!$result['success']) {
                return view('admin.members.index', [
                    'error' => $result['error'],
                    'availableUsers' => [],
                    'contacts' => $contacts,
                    'stats' => $stats,
                    'members' => $members,
                    'search' => $search
                ]);
            }

            $allUsers = $result['data']['users'] ?? [];
            
            // Marcar usuarios que YA están agregados a DDU
            $availableUsers = array_map(function($user) use ($existingUserIds) {
                $user['is_added'] = in_array($user['id'], $existingUserIds);
                return $user;
            }, $allUsers);
        }

        return view('admin.members.index', [
            'availableUsers' => $availableUsers,
            'contacts' => $contacts,
            'search' => $search,
            'stats' => $stats,
            'members' => $members
        ]);
    }

    /**
     * Añadir usuario a la empresa DDU
     */
    public function addMember(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string',
            'rol' => 'required|string|in:admin,miembro,administrador'
        ]);

        $result = $this->juntifyApi->addUserToCompany(
            $request->user_id,
            env('DDU_EMPRESA_ID'),
            $request->rol
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario añadido exitosamente a DDU',
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], $result['status'] ?? 500);
    }

    /**
     * Buscar usuarios disponibles (AJAX)
     */
    public function searchUsers(Request $request)
    {
        $search = $request->query('search');
        $result = $this->juntifyApi->getUsersList($search, 1);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'users' => $result['data']['users'] ?? [],
                'total' => $result['data']['total'] ?? 0
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 500);
    }

    /**
     * Actualizar rol de un miembro
     */
    public function updateRole(Request $request, string $userId)
    {
        $request->validate([
            'rol' => 'required|string|in:admin,miembro,administrador,colaborador'
        ]);

        $result = $this->juntifyApi->updateMemberRole(
            env('DDU_EMPRESA_ID'),
            $userId,
            $request->rol
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado correctamente',
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], $result['status'] ?? 500);
    }

    /**
     * Eliminar un miembro
     */
    public function removeMember(string $userId)
    {
        $result = $this->juntifyApi->removeMember(
            env('DDU_EMPRESA_ID'),
            $userId
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Miembro eliminado correctamente',
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], $result['status'] ?? 500);
    }
}
