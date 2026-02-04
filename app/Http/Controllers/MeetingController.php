<?php

namespace App\Http\Controllers;

use App\Models\MeetingTranscription;
use App\Services\JuDecryptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class MeetingController extends Controller
{
    public function show(MeetingTranscription $meeting): JsonResponse
    {
        $juntifyUserData = Session::get('juntify_user');
        $userId = $juntifyUserData['id'] ?? null;

        if (!$userId) {
            abort(401, 'Usuario no autenticado');
        }

        $user = (object)['id' => $userId];

        if (! $this->userCanAccessMeeting($meeting, $user)) {
            abort(403);
        }

        $meeting->load(['containers:id,name', 'groups:id,name', 'tasks' => function ($query) {
            $query->orderBy('fecha_limite')->orderBy('created_at');
        }]);

        $meetingData = [
            'id' => $meeting->id,
            'name' => $meeting->meeting_name,
            'description' => null, // Campo no disponible en la estructura actual
            'status' => 'completed', // Estado por defecto
            'started_at' => optional($meeting->started_at)->toIso8601String(),
            'ended_at' => optional($meeting->ended_at)->toIso8601String(),
            'duration_minutes' => $meeting->duration_minutes,
            'audio_url' => $meeting->audio_drive_id ? route('download.audio', $meeting) : null,
            'transcript_url' => $meeting->transcript_drive_id ? route('download.ju', $meeting) : null,
            'containers' => $meeting->containers->map(fn ($container) => [
                'id' => $container->id,
                'name' => $container->name,
            ])->values(),
            'groups' => $meeting->groups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
            ])->values(),
        ];

        $tasks = $meeting->tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'tarea' => $task->tarea,
                'prioridad' => $task->prioridad,
                'fecha_inicio' => optional($task->fecha_inicio)->toDateString(),
                'fecha_limite' => optional($task->fecha_limite)->toDateString(),
                'descripcion' => $task->descripcion,
                'progreso' => $task->progreso,
            ];
        })->values();

        return response()->json([
            'meeting' => $meetingData,
            'tasks' => $tasks,
        ]);
    }

    public function showDetails(Request $request): JsonResponse
    {
        $filePath = (string) $request->input('path');
        $audioUrl = (string) $request->input('audio_url', '');

        if ($filePath === '') {
            return response()->json(['error' => 'Ruta del archivo no proporcionada.'], 422);
        }

        $resolvedPath = $this->resolveFilePath($filePath);

        if (!$resolvedPath) {
            return response()->json(['error' => 'Archivo no encontrado.'], 404);
        }

        $decryptedData = JuDecryptionService::decrypt($resolvedPath);

        if (!$decryptedData) {
            return response()->json(['error' => 'No se pudo desencriptar la información de la reunión.'], 500);
        }

        // Debug: log de la estructura del archivo
        Log::info('Estructura del archivo .ju desencriptado:', [
            'available_keys' => array_keys($decryptedData),
            'data_sample' => array_slice($decryptedData, 0, 3, true) // Muestra solo las primeras 3 claves para debug
        ]);

        $summary = data_get($decryptedData, 'summary')
            ?? data_get($decryptedData, 'resumen')
            ?? 'No disponible';

        $keyPoints = data_get($decryptedData, 'key_points', []);

        // Debug: buscar claves similares si no existe key_points
        if (empty($keyPoints)) {
            $possibleKeys = [];
            foreach (array_keys($decryptedData) as $key) {
                if (stripos($key, 'key') !== false || stripos($key, 'point') !== false ||
                    stripos($key, 'clave') !== false || stripos($key, 'importante') !== false) {
                    $possibleKeys[$key] = $decryptedData[$key];
                }
            }
            Log::info('Claves similares a key_points encontradas:', $possibleKeys);
        }

        $keyPoints = is_array($keyPoints) ? array_values($keyPoints) : [];

        Log::info('Key points procesados:', ['count' => count($keyPoints), 'data' => $keyPoints]);

        $segments = data_get($decryptedData, 'segments')
            ?? data_get($decryptedData, 'transcription')
            ?? [];
        $segments = is_array($segments) ? array_values($segments) : [];

        return response()->json([
            'summary' => $summary,
            'key_points' => $keyPoints,
            'segments' => $segments,
            'audio_url' => $audioUrl,
            // Debug: incluir información de estructura
            'debug_info' => [
                'available_keys' => array_keys($decryptedData),
                'raw_key_points' => data_get($decryptedData, 'key_points', 'NOT_FOUND'),
                'possible_key_alternatives' => array_filter(array_keys($decryptedData), function($key) {
                    return stripos($key, 'key') !== false || stripos($key, 'point') !== false ||
                           stripos($key, 'clave') !== false || stripos($key, 'importante') !== false;
                })
            ]
        ]);
    }

    private function resolveFilePath(string $path): ?string
    {
        $candidatePaths = [$path];

        $isAbsolute = str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:\\\\/', $path) === 1;

        if (!$isAbsolute) {
            $candidatePaths[] = storage_path($path);
            $candidatePaths[] = storage_path('app/' . ltrim($path, '/\\'));
            $candidatePaths[] = base_path($path);
            $candidatePaths[] = public_path($path);
        }

        foreach ($candidatePaths as $candidate) {
            if (!$candidate) {
                continue;
            }

            $realPath = realpath($candidate);
            if ($realPath === false || !is_file($realPath)) {
                continue;
            }

            if (
                Str::startsWith($realPath, base_path()) ||
                Str::startsWith($realPath, storage_path())
            ) {
                return $realPath;
            }
        }

        return null;
    }

    private function userCanAccessMeeting(MeetingTranscription $meeting, ?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($meeting->user_id === $user->id || $meeting->username === $user->username) {
            return true;
        }

        return $meeting->groups()
            ->whereHas('members', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->exists();
    }
}
