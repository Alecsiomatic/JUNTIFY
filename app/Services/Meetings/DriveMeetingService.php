<?php

namespace App\Services\Meetings;

use App\Models\GoogleToken;
use App\Models\MeetingContentContainer;
use App\Models\MeetingTranscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DriveMeetingService
{
    /**
     * Retrieve meetings and statistics for a given user.
     */
    public function getOverviewForUser(object $user): array
    {
        // Obtener datos del usuario de Juntify
        $juntifyUser = DB::connection('juntify')
            ->table('users')
            ->where('id', $user->id)
            ->first();

        if (!$juntifyUser) {
            return [collect(), $this->calculateStats(collect()), null];
        }

        // Obtener reuniones directamente de juntify usando el username
        $juntifyMeetings = DB::connection('juntify')
            ->table('transcriptions_laravel')
            ->where('username', $juntifyUser->username)
            ->orderByDesc('created_at')
            ->get();

        // Convertir los datos de juntify a formato esperado por la vista
        $meetings = $juntifyMeetings->map(function ($meeting) {
            return (object) [
                'id' => (int) $meeting->id,
                'meeting_name' => (string) $meeting->meeting_name,
                'transcript_drive_id' => (string) $meeting->transcript_drive_id,
                'audio_drive_id' => (string) $meeting->audio_drive_id,
                'created_at' => Carbon::parse($meeting->created_at),
                'updated_at' => Carbon::parse($meeting->updated_at),
                'username' => (string) $meeting->username,
                // Campos adicionales para compatibilidad con vista
                'containers' => collect(),
                'groups' => collect(),
                'status' => 'completed', // Por defecto completed para reuniones de juntify
                'started_at' => Carbon::parse($meeting->created_at),
                'ended_at' => null,
                'duration_minutes' => null, // Mantener como null para reuniones de juntify
                'metadata' => (object) [ // Convertir a objeto para evitar errores de array
                    'ju_local_path' => $meeting->transcript_drive_id,
                    'ju_file_path' => $meeting->transcript_drive_id,
                    'ju_path' => $meeting->transcript_drive_id,
                ],
                'transcript_download_url' => (string) ($meeting->transcript_download_url ?? ''),
                'audio_download_url' => (string) ($meeting->audio_download_url ?? ''),
            ];
        });

        // Intentar obtener Google Token (puede no existir)
        $googleToken = null;
        try {
            $googleToken = GoogleToken::where('user_id', $user->id)
                ->with(['folders.subfolders' => fn ($query) => $query->orderBy('name')])
                ->first();
        } catch (\Exception $e) {
            // Si no existe el token, continuar sin él
        }

        $stats = $this->calculateStats($meetings);

        return [$meetings, $stats, $googleToken];
    }

    /**
     * Build statistics for the meetings collection.
     */
    protected function calculateStats(Collection $meetings): array
    {
        $now = Carbon::now();

        return [
            'total' => $meetings->count(),
            'programadas' => 0, // Para reuniones de juntify, todas están completadas
            'finalizadas' => $meetings->count(), // Todas las de juntify están finalizadas
            'esta_semana' => $meetings->filter(function ($meeting) use ($now) {
                try {
                    $meetingDate = Carbon::parse($meeting->created_at);
                    return $meetingDate->between($now->copy()->startOfWeek(), $now->copy()->endOfWeek());
                } catch (\Exception $e) {
                    return false;
                }
            })->count(),
        ];
    }
}
