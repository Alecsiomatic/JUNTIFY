<?php

namespace App\Http\Controllers;

use App\Services\JuDecryptionService;
use App\Services\JuFileDecryption;
use App\Services\JuntifyApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class JuntifyMeetingController extends Controller
{
    protected JuntifyApiService $juntifyApi;

    public function __construct(JuntifyApiService $juntifyApi)
    {
        $this->juntifyApi = $juntifyApi;
    }

    /**
     * Show meeting details from juntify database
     */
    public function show($meetingId): JsonResponse
    {
        $juntifyUserData = Session::get('juntify_user');
        $userId = $juntifyUserData['id'] ?? null;

        if (!$userId) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $user = (object)['id' => $userId];

        try {
            // Obtener datos del usuario de Juntify
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('id', $user->id)
                ->first();

            if (!$juntifyUser) {
                return response()->json(['error' => 'Usuario no encontrado en Juntify.'], 404);
            }

            // Obtener la reunión desde juntify
            $meeting = DB::connection('juntify')
                ->table('transcriptions_laravel')
                ->where('id', $meetingId)
                ->where('username', $juntifyUser->username)
                ->first();

            if (!$meeting) {
                return response()->json(['error' => 'Reunión no encontrada o no tienes permisos para acceder a ella.'], 404);
            }

            // Formatear los datos de la reunión
            $meetingData = [
                'id' => $meeting->id,
                'name' => $meeting->meeting_name,
                'description' => null,
                'status' => 'completed',
                'started_at' => $meeting->created_at,
                'ended_at' => null,
                'duration_minutes' => null,
                'audio_url' => $meeting->audio_download_url ?? null,
                'transcript_url' => $meeting->transcript_download_url ?? null,
                'audio_drive_id' => $meeting->audio_drive_id,
                'transcript_drive_id' => $meeting->transcript_drive_id,
                'containers' => [],
                'groups' => [],
            ];

            return response()->json([
                'meeting' => $meetingData,
                'tasks' => [],
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo reunión de Juntify: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }
    }

    /**
     * Show meeting details with transcript data
     */
    public function showDetails($transcriptionId): JsonResponse
    {
        try {
            $meetingId = $transcriptionId;
            $juntifyUserData = Session::get('juntify_user');
            $userId = $juntifyUserData['id'] ?? null;

            if (!$userId) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $user = (object)['id' => $userId];

            if (!$meetingId) {
                return response()->json(['error' => 'ID de reunión no proporcionado.'], 422);
            }

            // Obtener datos del usuario de Juntify
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('id', $user->id)
                ->first();

            if (!$juntifyUser) {
                return response()->json(['error' => 'Usuario no encontrado en Juntify.'], 404);
            }

            // Obtener la reunión desde juntify
            $meeting = DB::connection('juntify')
                ->table('transcriptions_laravel')
                ->where('id', $meetingId)
                ->where('username', $juntifyUser->username)
                ->first();

            if (!$meeting) {
                return response()->json(['error' => 'Reunión no encontrada.'], 404);
            }

            // Obtener tareas reales de la reunión
            $tasks = DB::connection('juntify')
                ->table('tasks_laravel')
                ->where('meeting_id', $meetingId)
                ->where('username', $juntifyUser->username)
                ->get();

            // Inicializar variables
            $segments = [];
            $summaryText = "Datos de reunión disponibles para: {$meeting->meeting_name}";
            $keyPoints = [];
            $audioBase64 = null;

            // ========================================
            // DESCARGAR Y DESENCRIPTAR ARCHIVO .JU
            // ========================================
            if ($meeting->transcript_drive_id) {
                Log::info('Intentando descargar archivo .ju', [
                    'meeting_id' => $meetingId,
                    'username' => $juntifyUser->username
                ]);

                $transcriptResult = $this->juntifyApi->downloadMeetingFile(
                    (int) $meetingId,
                    $juntifyUser->username,
                    'transcript'
                );

                if ($transcriptResult['success'] && !empty($transcriptResult['data']['file_content'])) {
                    $juFileContent = base64_decode($transcriptResult['data']['file_content']);
                    
                    Log::info('Archivo .ju descargado, intentando desencriptar', [
                        'size' => strlen($juFileContent)
                    ]);

                    // Desencriptar el contenido del .ju
                    $decryptedData = JuDecryptionService::decryptContent($juFileContent);

                    if ($decryptedData) {
                        Log::info('Archivo .ju desencriptado exitosamente', [
                            'keys' => array_keys($decryptedData)
                        ]);

                        // Extraer información del archivo desencriptado
                        $extractedInfo = JuFileDecryption::extractMeetingInfo($decryptedData);

                        $summaryText = $extractedInfo['summary'] ?? $summaryText;
                        $keyPoints = $extractedInfo['key_points'] ?? [];
                        $segments = $extractedInfo['segments'] ?? [];

                        Log::info('Información extraída del .ju', [
                            'summary_length' => strlen($summaryText),
                            'key_points_count' => count($keyPoints),
                            'segments_count' => count($segments)
                        ]);
                    } else {
                        Log::warning('No se pudo desencriptar el archivo .ju', [
                            'meeting_id' => $meetingId
                        ]);
                    }
                } else {
                    Log::warning('No se pudo descargar el archivo .ju', [
                        'meeting_id' => $meetingId,
                        'error' => $transcriptResult['error'] ?? 'Unknown error'
                    ]);
                }
            }

            // ========================================
            // DESCARGAR AUDIO
            // ========================================
            if ($meeting->audio_drive_id) {
                Log::info('Intentando descargar audio', [
                    'meeting_id' => $meetingId,
                    'username' => $juntifyUser->username
                ]);

                $audioResult = $this->juntifyApi->downloadMeetingFile(
                    (int) $meetingId,
                    $juntifyUser->username,
                    'audio'
                );

                if ($audioResult['success'] && !empty($audioResult['data']['file_content'])) {
                    $audioBase64 = $audioResult['data']['file_content'];
                    Log::info('Audio descargado exitosamente');
                } else {
                    Log::warning('No se pudo descargar el audio', [
                        'meeting_id' => $meetingId,
                        'error' => $audioResult['error'] ?? 'Unknown error'
                    ]);
                }
            }

            // Si no hay puntos clave del .ju, usar las tareas como alternativa
            if (empty($keyPoints)) {
                foreach ($tasks as $task) {
                    $keyPoints[] = $task->tarea . ($task->asignado && $task->asignado !== 'No asignado' ? " (Asignado a: {$task->asignado})" : "");
                }
            }

            // Si aún no hay puntos clave, usar mensaje genérico
            if (empty($keyPoints)) {
                $keyPoints = [
                    'Reunión registrada en Juntify',
                    'Fecha: ' . $meeting->created_at,
                    'Archivos de audio y transcripción disponibles'
                ];
            }

            return response()->json([
                'summary' => $summaryText,
                'key_points' => $keyPoints,
                'segments' => $segments,
                'audio_url' => $meeting->audio_download_url,
                'audio_base64' => $audioBase64,
                'tasks' => $tasks->map(function($task) {
                    return [
                        'id' => $task->id,
                        'task' => $task->tarea,
                        'description' => $task->descripcion,
                        'assigned_to' => $task->asignado,
                        'priority' => $task->prioridad,
                        'progress' => $task->progreso,
                        'start_date' => $task->fecha_inicio,
                        'due_date' => $task->fecha_limite,
                        'status' => $task->assignment_status
                    ];
                }),
                'meeting' => [
                    'id' => $meeting->id,
                    'name' => $meeting->meeting_name,
                    'created_at' => $meeting->created_at,
                    'audio_url' => $meeting->audio_download_url,
                    'transcript_url' => $meeting->transcript_download_url
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo detalles de reunión: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error al cargar los datos del servidor.'], 500);
        }
    }
}
