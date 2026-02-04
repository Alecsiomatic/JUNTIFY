<?php

namespace App\Services\Meetings;

use App\Services\JuntifyApiService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio para manejar reuniones desde Juntify API
 * 
 * Reemplaza DriveMeetingService para usar endpoints de Juntify
 * en lugar de acceso directo a la base de datos
 */
class JuntifyMeetingService
{
    protected JuntifyApiService $juntifyApi;

    public function __construct(JuntifyApiService $juntifyApi)
    {
        $this->juntifyApi = $juntifyApi;
    }

    /**
     * Obtener reuniones y estadísticas para un usuario
     * 
     * @param object $user Usuario desde sesión (stdClass con id, username, email)
     * @return array [Collection $meetings, array $stats]
     */
    public function getOverviewForUser(object $user): array
    {
        // Obtener reuniones del usuario desde Juntify API
        $meetingsResult = $this->juntifyApi->getUserMeetings($user->id);

        if (!$meetingsResult['success']) {
            // Si hay error, retornar colecciones vacías
            return [collect(), $this->calculateStats(collect())];
        }

        $data = $meetingsResult['data'];
        
        // Obtener IDs de reuniones para consultar tipos en batch
        $meetingIds = collect($data['meetings'] ?? [])->pluck('id')->toArray();
        $meetingTypes = $this->getMeetingTypesMap($meetingIds);
        
        // Convertir reuniones a Collection con formato esperado por las vistas
        $meetings = collect($data['meetings'] ?? [])->map(function ($meeting) use ($meetingTypes) {
            $meetingId = (int) $meeting['id'];
            $typeInfo = $meetingTypes[$meetingId] ?? [
                'type' => 'personal',
                'type_label' => 'Personal',
                'type_color' => '#8B5CF6',
                'container' => null
            ];
            
            return (object) [
                'id' => $meetingId,
                'meeting_name' => (string) $meeting['meeting_name'],
                'transcript_drive_id' => (string) ($meeting['transcript_drive_id'] ?? ''),
                'audio_drive_id' => (string) ($meeting['audio_drive_id'] ?? ''),
                'created_at' => Carbon::parse($meeting['created_at']),
                'updated_at' => Carbon::parse($meeting['updated_at']),
                'username' => (string) $meeting['username'],
                'status' => (string) ($meeting['status'] ?? 'completed'),
                'duration_minutes' => $meeting['duration_minutes'] ?? null,
                'transcript_download_url' => (string) ($meeting['transcript_download_url'] ?? ''),
                'audio_download_url' => (string) ($meeting['audio_download_url'] ?? ''),
                // Tipo de reunión para etiquetas
                'meeting_type' => $typeInfo['type'],
                'meeting_type_label' => $typeInfo['type_label'],
                'meeting_type_color' => $typeInfo['type_color'],
                // Contenedor si existe
                'container_id' => $typeInfo['container']['id'] ?? null,
                'container_name' => $typeInfo['container']['name'] ?? null,
                // Campos adicionales para compatibilidad con vistas
                'containers' => $typeInfo['container'] ? collect([(object) $typeInfo['container']]) : collect(),
                'groups' => collect(),
                'started_at' => Carbon::parse($meeting['created_at']),
                'ended_at' => null,
                'metadata' => (object) [
                    'ju_local_path' => $meeting['transcript_drive_id'] ?? '',
                    'ju_file_path' => $meeting['transcript_drive_id'] ?? '',
                    'ju_path' => $meeting['transcript_drive_id'] ?? '',
                ],
            ];
        });

        // Usar estadísticas de la API si están disponibles, sino calcularlas
        $stats = isset($data['stats']) 
            ? $this->normalizeStats($data['stats'], $meetings)
            : $this->calculateStats($meetings);

        return [$meetings, $stats];
    }

    /**
     * Obtener mapa de tipos de reuniones
     * 
     * @param array $meetingIds
     * @return array
     */
    protected function getMeetingTypesMap(array $meetingIds): array
    {
        if (empty($meetingIds)) {
            return [];
        }

        $result = $this->juntifyApi->getMeetingTypes($meetingIds);
        
        if (!$result['success']) {
            return [];
        }

        $types = [];
        $meetings = $result['data']['meetings'] ?? [];
        
        foreach ($meetings as $id => $meetingType) {
            if (isset($meetingType['success']) && $meetingType['success']) {
                $types[(int)$id] = [
                    'type' => $meetingType['type'] ?? 'personal',
                    'type_label' => $meetingType['type_label'] ?? 'Personal',
                    'type_color' => $meetingType['type_color'] ?? '#8B5CF6',
                    'container' => $meetingType['details']['container_id'] ?? null ? [
                        'id' => $meetingType['details']['container_id'],
                        'name' => $meetingType['details']['container_name'] ?? 'Contenedor'
                    ] : null
                ];
            }
        }

        return $types;
    }

    /**
     * Normalizar estadísticas desde API de Juntify
     */
    protected function normalizeStats(array $apiStats, Collection $meetings): array
    {
        return [
            'total' => $apiStats['total_meetings'] ?? $meetings->count(),
            'programadas' => 0, // Juntify no maneja programadas aún
            'finalizadas' => $apiStats['total_meetings'] ?? $meetings->count(),
            'esta_semana' => $apiStats['this_week'] ?? $this->countThisWeek($meetings),
        ];
    }

    /**
     * Calcular estadísticas desde las reuniones
     */
    protected function calculateStats(Collection $meetings): array
    {
        return [
            'total' => $meetings->count(),
            'programadas' => 0, // Para reuniones de Juntify, todas están completadas
            'finalizadas' => $meetings->count(),
            'esta_semana' => $this->countThisWeek($meetings),
        ];
    }

    /**
     * Contar reuniones de esta semana
     */
    protected function countThisWeek(Collection $meetings): int
    {
        $now = Carbon::now();
        
        return $meetings->filter(function ($meeting) use ($now) {
            try {
                $meetingDate = Carbon::parse($meeting->created_at);
                return $meetingDate->between(
                    $now->copy()->startOfWeek(), 
                    $now->copy()->endOfWeek()
                );
            } catch (\Exception $e) {
                return false;
            }
        })->count();
    }

    /**
     * Obtener grupos de reuniones del usuario
     * 
     * @param object $user Usuario desde sesión
     * @return Collection
     */
    public function getUserGroups(object $user): Collection
    {
        $groupsResult = $this->juntifyApi->getUserMeetingGroups(
            $user->id,
            includeMembers: false,
            includeMeetingsCount: true
        );

        if (!$groupsResult['success']) {
            return collect();
        }

        $data = $groupsResult['data'];
        
        return collect($data['groups'] ?? [])->map(function ($group) {
            return (object) [
                'id' => (int) $group['id'],
                'name' => (string) $group['name'],
                'description' => (string) ($group['description'] ?? ''),
                'owner_id' => (string) $group['owner_id'],
                'is_owner' => (bool) $group['is_owner'],
                'members_count' => (int) ($group['members_count'] ?? 0),
                'meetings_count' => (int) ($group['meetings_count'] ?? 0),
                'created_at' => Carbon::parse($group['created_at']),
                'updated_at' => Carbon::parse($group['updated_at']),
            ];
        });
    }

    /**
     * Obtener detalles de una reunión específica
     * 
     * @param int $meetingId
     * @return object|null
     */
    public function getMeetingDetails(int $meetingId): ?object
    {
        $result = $this->juntifyApi->getMeetingDetails($meetingId);

        if (!$result['success']) {
            return null;
        }

        $meeting = $result['data']['meeting'] ?? null;
        
        if (!$meeting) {
            return null;
        }

        return (object) [
            'id' => (int) $meeting['id'],
            'meeting_name' => (string) $meeting['meeting_name'],
            'username' => (string) $meeting['username'],
            'transcript_drive_id' => (string) ($meeting['transcript_drive_id'] ?? ''),
            'audio_drive_id' => (string) ($meeting['audio_drive_id'] ?? ''),
            'transcript_download_url' => (string) ($meeting['transcript_download_url'] ?? ''),
            'audio_download_url' => (string) ($meeting['audio_download_url'] ?? ''),
            'status' => (string) ($meeting['status'] ?? 'completed'),
            'duration_minutes' => $meeting['duration_minutes'] ?? null,
            'transcript_content' => (string) ($meeting['transcript_content'] ?? ''),
            'created_at' => Carbon::parse($meeting['created_at']),
            'updated_at' => Carbon::parse($meeting['updated_at']),
            'shared_with_groups' => collect($meeting['shared_with_groups'] ?? []),
            'user' => isset($meeting['user']) ? (object) $meeting['user'] : null,
        ];
    }
}
