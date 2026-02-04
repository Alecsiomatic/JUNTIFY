<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use App\Models\MeetingTranscription;
use App\Services\JuDecryptionService;
use App\Services\JuFileDecryption;
use App\Services\UserGoogleDriveService;
use App\Services\JuntifyApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class MeetingDetailsController extends Controller
{
    private JuntifyApiService $juntifyApi;

    public function __construct(JuntifyApiService $juntifyApi)
    {
        $this->juntifyApi = $juntifyApi;
    }

    /**
     * Obtener detalles completos desde Juntify API
     */
    public function showFromJuntify(string $meetingId)
    {
        try {
            // Obtener ID de usuario desde sesión
            $juntifyUser = Session::get('juntify_user', []);
            $userId = $juntifyUser['id'] ?? null;

            $result = $this->juntifyApi->getMeetingDetails($meetingId, $userId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], $result['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles de la reunión: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $meetingId, Request $request)
    {
        try {
            $juntifyUser = Session::get('juntify_user', []);
            $userId = $juntifyUser['id'] ?? null;

            // 1. Fetch complete meeting details from Juntify API
            $apiResult = $this->juntifyApi->getMeetingDetailsComplete($meetingId, $userId);

            if (!$apiResult['success']) {
                Log::error('API Error fetching meeting details', ['response' => $apiResult]);
                return back()->with('error', 'No se pudieron cargar los detalles de la reunión.');
            }

            $meetingData = $apiResult['data'] ?? [];
            $meetingInfo = [
                'summary' => (string)($meetingData['summary'] ?? ''),
                'key_points' => (array)($meetingData['key_points'] ?? []),
                // Prefer a plain transcription string if API already provides it
                'transcription' => (string)($meetingData['transcription'] ?? ''),
            ];

            // 2. Decrypt .ju file content if available
            if (empty($meetingInfo['summary']) || empty($meetingInfo['transcription'])) {
                if (!empty($meetingData['transcript_base64'])) {
                    $juFileContent = base64_decode($meetingData['transcript_base64']);
                    $decryptedData = JuDecryptionService::decryptContent($juFileContent);

                    if ($decryptedData) {
                        $extractedInfo = JuFileDecryption::extractMeetingInfo($decryptedData);
                        $meetingInfo['summary'] = $meetingInfo['summary'] ?: ($extractedInfo['summary'] ?? '');
                        $meetingInfo['key_points'] = !empty($meetingInfo['key_points']) ? $meetingInfo['key_points'] : ($extractedInfo['key_points'] ?? []);

                        $transcriptionText = collect($extractedInfo['segments'] ?? [])
                            ->map(function($segment) {
                                $speaker = $segment['speaker'] ?? null;
                                $text = $segment['text'] ?? '';
                                return $speaker ? "{$speaker}: {$text}" : $text;
                            })
                            ->implode("\n");

                        if (!empty($transcriptionText)) {
                            $meetingInfo['transcription'] = $transcriptionText;
                        }
                    } else {
                        Log::warning('Could not decrypt .ju content for meeting', ['id' => $meetingId]);
                    }
                }
            }

            // 4. Prepare final data for the view
            // Audio: support either base64 or url provided by API
            $audioBase64 = $meetingData['audio_base64'] ?? null;
            $audioUrl = $meetingData['audio_url'] ?? null;

            // If API provided segments and we still have no transcription string, compose one
            if (empty($meetingInfo['transcription']) && !empty($meetingData['segments']) && is_array($meetingData['segments'])) {
                $meetingInfo['transcription'] = collect($meetingData['segments'])
                    ->map(function($segment) {
                        $speaker = $segment['speaker'] ?? null;
                        $text = $segment['text'] ?? '';
                        return $speaker ? "{$speaker}: {$text}" : $text;
                    })
                    ->implode("\n");
            }

            $meeting = array_merge($meetingData, $meetingInfo, [
                'audio_base64' => $audioBase64,
                'audio_url' => $audioUrl,
            ]);

            Log::info('Final meeting data prepared for view', ['meeting_data' => $meeting]);

            return back()->with('selected_meeting', $meeting);

        } catch (\Exception $e) {
            Log::error('Failed to show meeting details', [
                'meeting_id' => $meetingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Ocurrió un error inesperado al cargar la reunión.');
        }
    }

    private function downloadFileContent(UserGoogleDriveService $driveService, string $fileId): ?string
    {
        $response = $driveService->downloadFile($fileId);

        return $response ? $response->getBody()->getContents() : null;
    }

    private function resolveToken(MeetingTranscription $transcription): ?GoogleToken
    {
        if ($transcription->user_id) {
            $token = GoogleToken::where('user_id', $transcription->user_id)->first();
            if ($token) {
                return $token;
            }
        }

        if ($transcription->username) {
            return GoogleToken::where('username', $transcription->username)->first();
        }

        return null;
    }

    /**
     * Verificar si el usuario tiene permisos para acceder a la reunión.
     */
    private function authorizeMeeting(MeetingTranscription $meeting): void
    {
        $user = auth()->user();

        // Verificar si es el propietario directo de la reunión
        if ($meeting->user_id && $meeting->user_id === optional($user)->id) {
            return;
        }

        if ($meeting->username && $meeting->username === optional($user)->username) {
            return;
        }

        // Verificar si el usuario tiene acceso a través de grupos
        if ($user && $this->hasGroupAccess($meeting, $user)) {
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
}
