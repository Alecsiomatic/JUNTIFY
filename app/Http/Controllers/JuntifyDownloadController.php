<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use App\Services\UserGoogleDriveService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JuntifyDownloadController extends Controller
{
    public function downloadAudio($meetingId): StreamedResponse
    {
        return $this->downloadFromDrive($meetingId, 'audio');
    }

    public function downloadJu($meetingId): StreamedResponse
    {
        return $this->downloadFromDrive($meetingId, 'transcript');
    }

    private function downloadFromDrive($meetingId, string $type): StreamedResponse
    {
        $juntifyUserData = Session::get('juntify_user');
        $userId = $juntifyUserData['id'] ?? null;

        if (!$userId) {
            abort(401, 'Usuario no autenticado');
        }

        // Obtener datos del usuario de Juntify
        $juntifyUser = DB::connection('juntify')
            ->table('users')
            ->where('id', $userId)
            ->first();

        if (!$juntifyUser) {
            abort(404, 'Usuario no encontrado en Juntify.');
        }

        // Obtener la reunión desde juntify
        $meeting = DB::connection('juntify')
            ->table('transcriptions_laravel')
            ->where('id', $meetingId)
            ->where('username', $juntifyUser->username)
            ->first();

        if (!$meeting) {
            abort(404, 'Reunión no encontrada o no tienes permisos para acceder a ella.');
        }

        $fileId = $type === 'audio'
            ? $meeting->audio_drive_id
            : $meeting->transcript_drive_id;

        if (!$fileId) {
            abort(404, "No se encontró el archivo de {$type} asociado a esta reunión.");
        }

        // Obtener token de Google usando username del juntifyUser
        $token = GoogleToken::where('username', $juntifyUser->username)->first();

        if (!$token) {
            abort(404, 'No se encontró un token de Google asociado al usuario.');
        }

        try {
            $driveService = new UserGoogleDriveService($token);
            $response = $driveService->downloadFile($fileId);

            $filename = $type === 'audio'
                ? "{$meeting->meeting_name}_audio.mp3"
                : "{$meeting->meeting_name}_transcript.ju";

            $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

            return response()->stream(function () use ($response) {
                if ($response instanceof ResponseInterface) {
                    echo $response->getBody()->getContents();
                } else {
                    echo $response;
                }
            }, 200, [
                'Content-Type' => $type === 'audio' ? 'audio/mpeg' : 'application/octet-stream',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);

        } catch (\Throwable $exception) {
            Log::error('No se pudo conectar con Google Drive.', [
                'meeting_id' => $meetingId,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);

            abort(500, 'Error al descargar el archivo desde Google Drive.');
        }
    }
}
