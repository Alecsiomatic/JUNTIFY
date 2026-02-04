<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\IntegrantesEmpresa;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberManagementController extends Controller
{
    /**
     * Display the member management page.
     */
    public function index()
    {
        // Obtener estadísticas de miembros DDU usando el nuevo sistema
        $dduMembers = \App\Models\IntegrantesEmpresa::getAllDduMembersWithUsers();

        $totalMembers = $dduMembers->count();
        $activeMembers = $dduMembers->count(); // Todos los DDU members están activos
        $adminMembers = $dduMembers->where('rol', 'administrador')->count();
        $collaboratorMembers = $dduMembers->where('rol', '!=', 'administrador')->count();

        // Preparar lista de miembros para la vista
        $members = $dduMembers->map(function ($member) {
            return (object) [
                'id' => $member->id,
                'user' => (object) [
                    'id' => $member->iduser,
                    'email' => $member->user_info->email ?? 'N/A',
                    'full_name' => $member->user_info->full_name ?? 'N/A',
                    'username' => $member->user_info->username ?? 'N/A',
                ],
                'role' => $member->rol,
                'is_active' => true,
                'created_at' => $member->created_at,
            ];
        });

        $stats = [
            'total' => $totalMembers,
            'active' => $activeMembers,
            'admins' => $adminMembers,
            'collaborators' => $collaboratorMembers
        ];

        return view('admin.members.index', compact('members', 'stats'));
    }

    /**
     * Search users by username or email.
     */
    public function searchUsers(Request $request)
    {
        try {
            $search = $request->get('search');

            if (empty($search) || strlen($search) < 2) {
                return response()->json(['users' => []]);
            }

            // Buscar usuarios de Juntify que no sean miembros DDU todavía
            $existingMemberUserIds = \App\Models\IntegrantesEmpresa::whereHas('empresa', function ($q) {
                $q->where('nombre_empresa', 'DDU');
            })->pluck('iduser');

            // Buscar en la base de datos de juntify
            $users = \DB::connection('juntify')
                ->table('users')
                ->where(function ($query) use ($search) {
                    $query->where('username', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%")
                          ->orWhere('full_name', 'LIKE', "%{$search}%");
                })
                ->whereNotIn('id', $existingMemberUserIds)
                ->limit(10)
                ->get(['id', 'username', 'full_name', 'email']);

            return response()->json(['users' => $users]);

        } catch (\Exception $e) {
            Log::error('Error en searchUsers: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al buscar usuarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a user as DDU member.
     */
    public function addMember(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'role' => 'required|in:administrador,miembro,colaborador'
        ]);

        try {
            DB::beginTransaction();

            // Verificar que el usuario exista en juntify
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('id', $request->user_id)
                ->first();

            if (!$juntifyUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado en Juntify.'
                ], 422);
            }

            // Verificar que el usuario no sea ya miembro DDU
            $existingMember = \App\Models\IntegrantesEmpresa::whereHas('empresa', function ($q) {
                $q->where('nombre_empresa', 'DDU');
            })->where('iduser', $request->user_id)->first();

            if ($existingMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este usuario ya es miembro de DDU.'
                ], 422);
            }

            // Obtener la empresa DDU
            $dduEmpresa = \App\Models\Empresa::where('nombre_empresa', 'DDU')->first();
            if (!$dduEmpresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa DDU no encontrada.'
                ], 422);
            }

            // Crear el miembro DDU
            $member = \App\Models\IntegrantesEmpresa::create([
                'iduser' => $request->user_id,
                'empresa_id' => $dduEmpresa->id,
                'rol' => $request->role,
                'permisos' => [] // Array vacío por defecto
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Miembro DDU agregado exitosamente.',
                'member' => [
                    'id' => $member->id,
                    'user' => [
                        'id' => $juntifyUser->id,
                        'email' => $juntifyUser->email,
                        'full_name' => $juntifyUser->full_name,
                        'username' => $juntifyUser->username,
                    ],
                    'role' => $member->rol,
                    'created_at' => $member->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar el miembro: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update member role.
     */
    public function updateMember(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:administrador,miembro,colaborador'
        ]);

        try {
            $member = \App\Models\IntegrantesEmpresa::whereHas('empresa', function ($q) {
                $q->where('nombre_empresa', 'DDU');
            })->findOrFail($id);

            $member->update([
                'rol' => $request->role
            ]);

            // Obtener info del usuario de juntify
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('id', $member->iduser)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Miembro actualizado exitosamente.',
                'member' => [
                    'id' => $member->id,
                    'user' => [
                        'id' => $juntifyUser->id,
                        'email' => $juntifyUser->email,
                        'full_name' => $juntifyUser->full_name,
                        'username' => $juntifyUser->username,
                    ],
                    'role' => $member->rol,
                    'updated_at' => $member->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el miembro: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle member status (active/inactive).
     * Nota: En el nuevo sistema DDU, todos los miembros están activos por defecto.
     */
    public function toggleStatus($id)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'En el nuevo sistema DDU, todos los miembros están activos por defecto.',
                'member' => null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove member from DDU.
     */
    public function removeMember($id)
    {
        try {
            $member = \App\Models\IntegrantesEmpresa::whereHas('empresa', function ($q) {
                $q->where('nombre_empresa', 'DDU');
            })->findOrFail($id);

            // Obtener nombre del usuario antes de eliminar
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('id', $member->iduser)
                ->first();

            $userName = $juntifyUser->full_name ?? $juntifyUser->username ?? 'Usuario';

            $member->delete();

            return response()->json([
                'success' => true,
                'message' => "Miembro {$userName} eliminado exitosamente de DDU."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el miembro: ' . $e->getMessage()
            ], 500);
        }
    }
}
