<?php

namespace App\Http\Controllers;

use App\Services\JuntifyApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class CompanyGroupController extends Controller
{
    protected JuntifyApiService $juntifyApi;

    public function __construct(JuntifyApiService $juntifyApi)
    {
        $this->juntifyApi = $juntifyApi;
    }

    /**
     * Obtener datos del usuario y empresa de la sesión
     */
    protected function getSessionData(): array
    {
        $juntifyUser = Session::get('juntify_user', []);
        $juntifyCompany = Session::get('juntify_company', []);

        return [
            'user_id' => $juntifyUser['id'] ?? null,
            'username' => $juntifyUser['username'] ?? null,
            'empresa_id' => $juntifyCompany['id'] ?? null,
        ];
    }

    /**
     * Listar grupos de la empresa
     */
    public function index(): JsonResponse
    {
        $session = $this->getSessionData();

        if (!$session['empresa_id']) {
            Log::warning('CompanyGroupController::index - empresa_id no encontrada en sesión', $session);
            return response()->json([
                'success' => true,
                'groups' => [],
                'total' => 0,
                'message' => 'No hay empresa asociada a tu cuenta'
            ]);
        }

        $result = $this->juntifyApi->getCompanyGroups($session['empresa_id']);

        if (!$result['success']) {
            Log::warning('CompanyGroupController::index - Error al obtener grupos', [
                'empresa_id' => $session['empresa_id'],
                'error' => $result['error'] ?? 'Unknown error'
            ]);
            // Si hay error de conexión, devolver lista vacía en lugar de error
            return response()->json([
                'success' => true,
                'groups' => [],
                'total' => 0,
                'message' => $result['error'] ?? 'No se pudieron cargar los grupos'
            ]);
        }

        return response()->json([
            'success' => true,
            'groups' => $result['data']['groups'] ?? [],
            'total' => $result['data']['total'] ?? 0
        ]);
    }

    /**
     * Crear un nuevo grupo
     */
    public function store(Request $request): JsonResponse
    {
        $session = $this->getSessionData();

        if (!$session['empresa_id'] || !$session['user_id']) {
            return response()->json(['success' => false, 'error' => 'Sesión inválida'], 401);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        $result = $this->juntifyApi->createCompanyGroup(
            $session['empresa_id'],
            $validated['nombre'],
            $validated['descripcion'] ?? null,
            $session['user_id']  // Enviar UUID en lugar de username
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Grupo creado exitosamente',
            'group' => $result['data']['group'] ?? null
        ], 201);
    }

    /**
     * Ver detalles de un grupo
     */
    public function show(string $grupoId): JsonResponse
    {
        $session = $this->getSessionData();

        if (!$session['empresa_id']) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada'], 404);
        }

        $result = $this->juntifyApi->getCompanyGroup($session['empresa_id'], (int) $grupoId);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 404);
        }

        return response()->json([
            'success' => true,
            'group' => $result['data']['group'] ?? null
        ]);
    }

    /**
     * Eliminar un grupo
     */
    public function destroy(string $grupoId): JsonResponse
    {
        $session = $this->getSessionData();

        if (!$session['empresa_id']) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada'], 404);
        }

        $result = $this->juntifyApi->deleteCompanyGroup($session['empresa_id'], (int) $grupoId);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Grupo eliminado exitosamente'
        ]);
    }

    /**
     * Añadir miembro a un grupo
     */
    public function addMember(Request $request, string $grupoId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|string',
            'rol' => 'nullable|string|in:administrador,colaborador,invitado',
        ]);

        $result = $this->juntifyApi->addGroupMember(
            (int) $grupoId,
            $validated['user_id'],
            $validated['rol'] ?? 'colaborador'
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Miembro añadido exitosamente',
            'member' => $result['data']['member'] ?? null
        ], 201);
    }

    /**
     * Actualizar rol de miembro
     */
    public function updateMemberRole(Request $request, string $grupoId, string $memberId): JsonResponse
    {
        $validated = $request->validate([
            'rol' => 'required|string|in:administrador,colaborador,invitado',
        ]);

        $result = $this->juntifyApi->updateGroupMemberRole((int) $grupoId, (int) $memberId, $validated['rol']);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado exitosamente'
        ]);
    }

    /**
     * Eliminar miembro de grupo
     */
    public function removeMember(string $grupoId, string $memberId): JsonResponse
    {
        $result = $this->juntifyApi->removeGroupMember((int) $grupoId, (int) $memberId);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Miembro eliminado exitosamente'
        ]);
    }

    /**
     * Compartir reunión con grupo
     */
    public function shareMeeting(Request $request, string $grupoId): JsonResponse
    {
        $session = $this->getSessionData();

        if (!$session['username']) {
            return response()->json(['success' => false, 'error' => 'Usuario no autenticado'], 401);
        }

        $validated = $request->validate([
            'meeting_id' => 'required',
            'permisos' => 'nullable|array',
            'permisos.ver_audio' => 'nullable|boolean',
            'permisos.ver_transcript' => 'nullable|boolean',
            'permisos.descargar' => 'nullable|boolean',
            'mensaje' => 'nullable|string|max:500',
        ]);

        $permisos = $validated['permisos'] ?? [
            'ver_audio' => true,
            'ver_transcript' => true,
            'descargar' => true
        ];

        $result = $this->juntifyApi->shareMeetingWithGroup(
            (int) $grupoId,
            (int) $validated['meeting_id'],
            $session['username'],
            $permisos,
            $validated['mensaje'] ?? null
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reunión compartida exitosamente',
            'shared_meeting' => $result['data']['shared_meeting'] ?? null
        ], 201);
    }

    /**
     * Listar reuniones compartidas del grupo
     */
    public function sharedMeetings(string $grupoId): JsonResponse
    {
        $result = $this->juntifyApi->getGroupSharedMeetings((int) $grupoId);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 500);
        }

        return response()->json([
            'success' => true,
            'shared_meetings' => $result['data']['shared_meetings'] ?? []
        ]);
    }

    /**
     * Dejar de compartir reunión
     */
    public function unshareMeeting(string $grupoId, string $meetingId): JsonResponse
    {
        $result = $this->juntifyApi->unshareMeetingFromGroup((int) $grupoId, (int) $meetingId);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Se dejó de compartir la reunión'
        ]);
    }

    /**
     * Obtener grupos del usuario actual
     */
    public function userGroups(): JsonResponse
    {
        $session = $this->getSessionData();

        if (!$session['user_id']) {
            return response()->json(['success' => false, 'error' => 'Usuario no autenticado'], 401);
        }

        $result = $this->juntifyApi->getUserCompanyGroups($session['user_id']);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 500);
        }

        return response()->json([
            'success' => true,
            'groups' => $result['data']['groups'] ?? [],
            'total' => $result['data']['total'] ?? 0
        ]);
    }

    /**
     * Obtener miembros de la empresa (para selector)
     */
    public function companyMembers(): JsonResponse
    {
        $session = $this->getSessionData();

        if (!$session['empresa_id']) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada'], 404);
        }

        $result = $this->juntifyApi->getCompanyMembers($session['empresa_id']);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 500);
        }

        return response()->json([
            'success' => true,
            'members' => $result['data']['members'] ?? [],
            'total' => $result['data']['total'] ?? 0
        ]);
    }

    /**
     * Descargar archivos de reunión compartida
     */
    public function downloadSharedMeetingFiles(Request $request, string $grupoId, string $meetingId): JsonResponse
    {
        $session = $this->getSessionData();

        if (!$session['empresa_id'] || !$session['user_id']) {
            return response()->json(['success' => false, 'error' => 'Sesión inválida'], 401);
        }

        $fileType = $request->query('file_type', 'both');

        $result = $this->juntifyApi->downloadSharedMeetingFiles(
            $session['empresa_id'],
            (int) $grupoId,
            (int) $meetingId,
            $session['user_id'],
            $fileType
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], $result['status'] ?? 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * Actualizar un grupo
     */
    public function update(Request $request, string $grupoId): JsonResponse
    {
        $session = $this->getSessionData();

        if (!$session['empresa_id']) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $result = $this->juntifyApi->updateCompanyGroup(
            $session['empresa_id'],
            (int) $grupoId,
            $validated
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Grupo actualizado exitosamente'
        ]);
    }

    /**
     * Obtener detalles completos de reunión compartida (con transcripción procesada)
     */
    public function getSharedMeetingDetails(Request $request, string $grupoId, string $meetingId): JsonResponse
    {
        $session = $this->getSessionData();

        if (!$session['empresa_id'] || !$session['user_id']) {
            return response()->json(['success' => false, 'error' => 'Sesión inválida'], 401);
        }

        // Primero obtener los archivos de la reunión compartida
        $result = $this->juntifyApi->downloadSharedMeetingFiles(
            $session['empresa_id'],
            (int) $grupoId,
            (int) $meetingId,
            $session['user_id'],
            'both'
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], $result['status'] ?? 500);
        }

        $data = $result['data'];
        $permisos = $data['permisos'] ?? [];

        // Inicializar variables de respuesta
        $response = [
            'success' => true,
            'meeting_id' => $meetingId,
            'meeting_name' => $data['meeting_name'] ?? 'Reunión compartida',
            'shared_by' => $data['shared_by'] ?? null,
            'permisos' => $permisos,
            'summary' => 'Información de reunión disponible',
            'key_points' => [],
            'segments' => [],
            'audio_base64' => null,
        ];

        // Procesar el archivo .ju si está disponible y tiene permiso
        if (isset($data['transcript']['file_content']) && ($permisos['ver_transcript'] ?? true)) {
            try {
                $juFileContent = base64_decode($data['transcript']['file_content']);
                
                // Usar el servicio de desencriptación
                $decryptedData = \App\Services\JuDecryptionService::decryptContent($juFileContent);

                if ($decryptedData) {
                    // Extraer información del archivo desencriptado
                    $extractedInfo = \App\Services\JuFileDecryption::extractMeetingInfo($decryptedData);

                    $response['summary'] = $extractedInfo['summary'] ?? $response['summary'];
                    $response['key_points'] = $extractedInfo['key_points'] ?? [];
                    $response['segments'] = $extractedInfo['segments'] ?? [];
                }
            } catch (\Exception $e) {
                Log::warning('Error procesando archivo .ju de reunión compartida', [
                    'meeting_id' => $meetingId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Incluir audio si tiene permiso
        if (isset($data['audio']['file_content']) && ($permisos['ver_audio'] ?? true)) {
            $response['audio_base64'] = $data['audio']['file_content'];
        }

        // Si no hay puntos clave, agregar algunos genéricos
        if (empty($response['key_points'])) {
            $response['key_points'] = [
                'Reunión compartida por ' . ($data['shared_by'] ?? 'un miembro del grupo'),
                'Fecha: ' . date('d/m/Y H:i'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }
}
