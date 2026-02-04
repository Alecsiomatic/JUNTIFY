<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use App\Models\MeetingTranscription;
use App\Services\UserGoogleDriveService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function downloadAudio(MeetingTranscription $meeting): StreamedResponse
    {
        return $this->downloadFromDrive($meeting, 'audio');
    }

    public function downloadJu(MeetingTranscription $meeting): StreamedResponse
    {
        return $this->downloadFromDrive($meeting, 'transcript');
    }

    private function downloadFromDrive(MeetingTranscription $meeting, string $type): StreamedResponse
    {
        $this->authorizeMeeting($meeting);

        $fileId = $type === 'audio'
            ? $meeting->audio_drive_id
            : $meeting->transcript_drive_id;

        if (! $fileId) {
            abort(404, "No se encontró el archivo de {$type} asociado a esta reunión.");
        }

        $token = $this->resolveToken($meeting);

        if (! $token) {
            abort(404, 'No se encontró un token de Google asociado al usuario propietario.');
        }

        try {
            $driveService = new UserGoogleDriveService($token);
            $response = $driveService->downloadFile($fileId);
        } catch (\Throwable $exception) {
            Log::error('No se pudo conectar con Google Drive.', [
                'meeting_id' => $meeting->id,
                'type' => $type,
                'exception' => $exception,
            ]);

            abort(500, 'No fue posible recuperar el archivo desde Google Drive.');
        }

        if (! $response) {
            abort(404, 'El archivo solicitado no está disponible en Google Drive.');
        }

        return $this->streamResponse($meeting, $response, $type);
    }

    private function authorizeMeeting(MeetingTranscription $meeting): void
    {
        $juntifyUserData = Session::get('juntify_user');
        $userId = $juntifyUserData['id'] ?? null;
        $username = $juntifyUserData['username'] ?? null;

        if (!$userId) {
            abort(401, 'Usuario no autenticado');
        }

        $user = (object)[
            'id' => $userId,
            'username' => $username
        ];

        // Verificar si es el propietario directo de la reunión
        if ($meeting->user_id && $meeting->user_id === $user->id) {
            return;
        }

        if ($meeting->username && $meeting->username === $user->username) {
            return;
        }

        // Verificar si el usuario tiene acceso a través de grupos
        if ($this->hasGroupAccess($meeting, $user)) {
            return;
        }

        abort(403, 'No tienes permisos para acceder a esta reunión.');
    }

    /**
     * Verificar si el usuario tiene acceso a la reunión a través de grupos compartidos.
     */
    private function hasGroupAccess(MeetingTranscription $meeting, $user): bool
    {
        return $meeting->groups()
            ->whereHas('members', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->exists();
    }

    private function resolveToken(MeetingTranscription $meeting): ?GoogleToken
    {
        if ($meeting->user_id) {
            $token = GoogleToken::where('user_id', $meeting->user_id)->first();

            if ($token) {
                return $token;
            }
        }

        if ($meeting->username) {
            return GoogleToken::where('username', $meeting->username)->first();
        }

        return null;
    }

    private function streamResponse(MeetingTranscription $meeting, ResponseInterface $response, string $type): StreamedResponse
    {
        $body = $response->getBody();
        $contentType = $response->getHeaderLine('Content-Type') ?: $this->defaultContentType($type);
        $fileName = $this->buildFileName($meeting, $type, $contentType);
        $disposition = $this->contentDisposition($fileName, $type);
        $contentLength = $response->getHeaderLine('Content-Length');

        return response()->stream(function () use ($body) {
            try {
                while (! $body->eof()) {
                    echo $body->read(1024 * 8);
                }
            } finally {
                $body->close();
            }
        }, 200, array_filter([
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition,
            'Content-Length' => $contentLength ?: null,
        ]));
    }

    private function buildFileName(MeetingTranscription $meeting, string $type, string $contentType): string
    {
        $base = Str::slug($meeting->meeting_name ?? '') ?: 'reunion-' . $meeting->id;
        $extension = $type === 'audio'
            ? $this->guessAudioExtension($contentType)
            : 'ju';

        return "{$base}.{$extension}";
    }

    private function guessAudioExtension(string $contentType): string
    {
        return match (strtolower($contentType)) {
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/webm' => 'webm',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/mp4', 'audio/aac' => 'm4a',
            'audio/ogg', 'application/ogg' => 'ogg',
            default => 'ogg',
        };
    }

    private function defaultContentType(string $type): string
    {
        return $type === 'audio'
            ? 'audio/ogg'
            : 'application/octet-stream';
    }

    private function contentDisposition(string $fileName, string $type): string
    {
        $dispositionType = $type === 'audio' ? 'inline' : 'attachment';

        return sprintf('%s; filename="%s"', $dispositionType, $fileName);
    }
}
