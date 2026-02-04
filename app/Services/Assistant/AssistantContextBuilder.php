<?php

namespace App\Services\Assistant;

use App\Models\AssistantConversation;
use App\Models\AssistantDocument;
use App\Models\User;
use App\Services\Calendar\GoogleCalendarService;
use App\Services\JuntifyApiService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssistantContextBuilder
{
    public function __construct(
        private readonly GoogleCalendarService $calendarService,
        private readonly JuntifyApiService $juntifyApi
    ) {
    }

    public function build(User $user, AssistantConversation $conversation, array $options = []): array
    {
        $meetingIds = Arr::wrap($options['meetings'] ?? []);
        $containerIds = Arr::wrap($options['containers'] ?? []);
        $includeCalendar = (bool) ($options['include_calendar'] ?? true);
        $userMessage = $options['user_message'] ?? null;

        // Debug temporal
        Log::info('AssistantContextBuilder::build called', [
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'meetingIds' => $meetingIds,
            'containerIds' => $containerIds,
            'includeCalendar' => $includeCalendar,
        ]);

        $contextParts = [];

        // Incluir información de fecha y hora actual SIEMPRE
        $contextParts[] = $this->buildCurrentDateTimeContext();

        if (! empty($meetingIds)) {
            $contextParts[] = $this->buildMeetingsContext($user, $meetingIds, $userMessage);
        }

        if (! empty($containerIds)) {
            $contextParts[] = $this->buildContainersContext($user, $containerIds);
        }

        if ($includeCalendar) {
            $contextParts[] = $this->buildCalendarContext($user);
        }

        $contextParts[] = $this->buildDocumentsContext($conversation);

        $contextParts = array_filter($contextParts);

        return [
            'text' => implode("\n\n", $contextParts),
            'documents' => $conversation->documents,
        ];
    }

    protected function buildMeetingsContext(User $user, array $meetingIds, ?string $query = null): ?string
    {
        Log::info('buildMeetingsContext called', [
            'user_id' => $user->id,
            'meetingIds' => $meetingIds,
            'meetingIds_count' => count($meetingIds),
        ]);

        if (empty($meetingIds)) {
            return null;
        }

        // Enviar la transcripción completa para las reuniones seleccionadas (sin truncar)
        $segmentLimit = null; // null = sin límite

        $lines = [
            'Contexto de reuniones seleccionadas:',
        ];

        $meetingsFound = 0;

        // Obtener cada reunión individualmente desde Juntify
        foreach ($meetingIds as $meetingId) {
            $meetingResult = $this->juntifyApi->getMeetingDetails((int) $meetingId);

            if (!$meetingResult['success'] || empty($meetingResult['data'])) {
                Log::warning('No se pudo obtener reunión desde Juntify', [
                    'meeting_id' => $meetingId
                ]);
                continue;
            }

            $meetingData = $meetingResult['data']['meeting'] ?? $meetingResult['data'];
            $meetingsFound++;

            $meetingName = $meetingData['meeting_name'] ?? $meetingData['name'] ?? 'Sin título';
            $lines[] = sprintf('- Reunión "%s" (completada)', $meetingName);

            // Obtener detalles reales de la reunión desde Google Drive usando el transcript_drive_id
            $transcriptDriveId = $meetingData['transcript_drive_id'] ?? null;
            $meetingOwner = $meetingData['username'] ?? $meetingData['owner_username'] ?? null;
            
            $meetingInfo = $this->getMeetingDetailsFromDriveById($transcriptDriveId, $meetingOwner, $user, (int) $meetingId);

            if ($meetingInfo['summary'] && $meetingInfo['summary'] !== 'Resumen no disponible.') {
                $lines[] = '  Resumen: ' . Str::of($meetingInfo['summary'])->squish();
            }

            if (!empty($meetingInfo['key_points'])) {
                $lines[] = '  Puntos clave:';
                foreach ($meetingInfo['key_points'] as $point) {
                    $pointText = is_array($point) ? ($point['description'] ?? $point['title'] ?? $point['text'] ?? '') : (string)$point;
                    if ($pointText) {
                        $lines[] = '    • ' . Str::of($pointText)->squish();
                    }
                }
            }

            if (!empty($meetingInfo['segments'])) {
                $lines[] = '  Extracto de transcripción:';
                $transcriptLines = [];

                // Si no hay límite (necesitamos la transcripción completa), iteramos todos los segmentos
                if (is_null($segmentLimit)) {
                    foreach ($meetingInfo['segments'] as $segment) {
                        $speaker = is_array($segment) ? ($segment['speaker'] ?? $segment['role'] ?? 'Hablante') : 'Hablante';
                        $text = is_array($segment) ? ($segment['text'] ?? $segment['content'] ?? $segment['sentence'] ?? '') : (string)$segment;
                        if ($text) {
                            $transcriptLines[] = "    {$speaker}: " . Str::of($text)->squish();
                        }
                    }
                } else {
                    foreach (array_slice($meetingInfo['segments'], 0, $segmentLimit) as $segment) {
                        $speaker = is_array($segment) ? ($segment['speaker'] ?? $segment['role'] ?? 'Hablante') : 'Hablante';
                        $text = is_array($segment) ? ($segment['text'] ?? $segment['content'] ?? $segment['sentence'] ?? '') : (string)$segment;
                        if ($text) {
                            $transcriptLines[] = "    {$speaker}: " . Str::of($text)->squish()->limit(200);
                        }
                    }
                }

                if (!empty($transcriptLines)) {
                    $lines = array_merge($lines, $transcriptLines);
                    if (!is_null($segmentLimit) && count($meetingInfo['segments']) > $segmentLimit) {
                        $lines[] = '    [...más contenido disponible]';
                    }
                }
            }
        }

        Log::info('Meetings processed from Juntify', [
            'meetings_found' => $meetingsFound,
        ]);

        if ($meetingsFound === 0) {
            return null;
        }

        return implode("\n", $lines);
    }

    protected function buildContainersContext(User $user, array $containerIds): ?string
    {
        // Los contenedores por ahora no se usan - retornar null
        // En el futuro se puede implementar un endpoint en Juntify para contenedores
        if (empty($containerIds)) {
            return null;
        }

        return null;
    }

    protected function buildCalendarContext(User $user): ?string
    {
        try {
            $events = $this->calendarService->listUpcomingEvents($user, Carbon::now(), Carbon::now()->addWeeks(2));
        } catch (\Throwable $exception) {
            Log::warning('No se pudo obtener el calendario para el asistente.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if ($events->isEmpty()) {
            return 'No hay eventos próximos en el calendario durante las próximas dos semanas.';
        }

        $lines = ['Eventos próximos del calendario:'];

        foreach ($events as $event) {
            $lines[] = sprintf('- %s el %s de %s a %s',
                $event['summary'],
                Carbon::parse($event['start'])->translatedFormat('d \d\e F \a \l\a\s H:i'),
                Carbon::parse($event['start'])->format('H:i'),
                Carbon::parse($event['end'])->format('H:i')
            );

            if (! empty($event['description'])) {
                $lines[] = '  Descripción: ' . Str::of($event['description'])->squish()->limit(200);
            }
        }

        return implode("\n", $lines);
    }

    protected function buildDocumentsContext(AssistantConversation $conversation): ?string
    {
        $documents = $conversation->documents;

        if ($documents->isEmpty()) {
            return null;
        }

        $lines = ['Documentos adjuntos analizados:'];

        /** @var AssistantDocument $document */
        foreach ($documents as $document) {
            $lines[] = sprintf('- %s (%s)', $document->original_name, $document->mime_type ?? 'tipo desconocido');

            if ($document->summary) {
                $lines[] = '  Resumen: ' . Str::of($document->summary)->squish()->limit(300);
            } elseif ($document->extracted_text) {
                $lines[] = '  Extracto: ' . Str::of($document->extracted_text)->squish()->limit(300);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Obtiene los detalles de una reunión desde Google Drive usando el ID del archivo
     * Primero intenta usar Juntify para descargar (maneja delegated tokens)
     * Si falla, intenta directamente con el token del usuario
     */
    private function getMeetingDetailsFromDriveById(?string $transcriptDriveId, ?string $ownerUsername, User $user, ?int $meetingId = null): array
    {
        $defaultInfo = [
            'summary' => 'Resumen no disponible.',
            'key_points' => [],
            'segments' => [],
        ];

        if (!$transcriptDriveId) {
            return $defaultInfo;
        }

        try {
            // Primero intentar descargar a través de Juntify (maneja delegated tokens para reuniones compartidas)
            if ($meetingId && $ownerUsername) {
                $downloadResult = $this->juntifyApi->downloadMeetingFile($meetingId, $ownerUsername, 'transcript');
                
                if ($downloadResult['success'] && isset($downloadResult['data']['file_content'])) {
                    $juFileContent = base64_decode($downloadResult['data']['file_content']);
                    
                    if ($juFileContent) {
                        $decryptedData = \App\Services\JuDecryptionService::decryptContent($juFileContent);
                        
                        if ($decryptedData) {
                            return \App\Services\JuFileDecryption::extractMeetingInfo($decryptedData);
                        }
                    }
                }
            }

            // Fallback: intentar con token del usuario actual o del propietario
            $token = $user->googleToken;
            
            if (!$token && $ownerUsername && $ownerUsername !== $user->username) {
                $owner = \App\Models\User::where('username', $ownerUsername)->first();
                $token = $owner?->googleToken;
            }

            if (!$token) {
                Log::warning('No se encontró token de Google para obtener detalles de reunión', [
                    'transcript_drive_id' => $transcriptDriveId,
                    'owner_username' => $ownerUsername,
                    'user_id' => $user->id
                ]);
                return $defaultInfo;
            }

            $driveService = new \App\Services\UserGoogleDriveService($token);
            $juFileContent = $this->downloadFileContent($driveService, $transcriptDriveId);

            if ($juFileContent) {
                $decryptedData = \App\Services\JuDecryptionService::decryptContent($juFileContent);

                if ($decryptedData) {
                    return \App\Services\JuFileDecryption::extractMeetingInfo($decryptedData);
                }
            }

            return $defaultInfo;

        } catch (\Exception $e) {
            Log::warning('Error obteniendo detalles de reunión para asistente', [
                'transcript_drive_id' => $transcriptDriveId,
                'meeting_id' => $meetingId,
                'error' => $e->getMessage()
            ]);
            return $defaultInfo;
        }
    }

    /**
     * Descarga el contenido de un archivo desde Google Drive
     */
    private function downloadFileContent(\App\Services\UserGoogleDriveService $driveService, string $fileId): ?string
    {
        try {
            $response = $driveService->downloadFile($fileId);
            return $response ? $response->getBody()->getContents() : null;
        } catch (\Exception $e) {
            Log::warning('Error descargando archivo de Google Drive', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Determina si la consulta requiere acceso a la transcripción completa
     */
    protected function requiresFullTranscription(?string $query): bool
    {
        if (!$query) {
            return false;
        }

        $query = strtolower($query);

        // Palabras clave que indican necesidad de transcripción completa
        $fullTranscriptionKeywords = [
            'fragmentos',
            'intervino',
            'dijo',
            'menciono',
            'hablo',
            'pregunto',
            'respondio',
            'comento',
            'participo',
            'converso',
            'dialogo',
            'discutio',
            'opino',
            'conversacion',
            'todas las veces',
            'cada vez que',
            'cuando dijo',
            'momentos donde',
            'partes donde',
            'citas',
            'exactas palabras',
            'textual',
            'literal'
        ];

        foreach ($fullTranscriptionKeywords as $keyword) {
            if (str_contains($query, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function buildCurrentDateTimeContext(): string
    {
        $now = Carbon::now();
        $timezone = config('app.timezone', 'America/Mexico_City');

        return sprintf(
            "FECHA Y HORA ACTUAL: %s (Zona horaria: %s)\n" .
            "Para calcular fechas relativas:\n" .
            "- 'mañana' = %s\n" .
            "- 'pasado mañana' = %s\n" .
            "- 'la próxima semana' = %s\n" .
            "- 'el próximo mes' = %s\n" .
            "IMPORTANTE: Siempre usa el año %s para nuevos eventos, NUNCA años anteriores.",
            $now->translatedFormat('l d \d\e F \d\e Y \a \l\a\s H:i'),
            $timezone,
            $now->addDay()->translatedFormat('l d \d\e F \d\e Y'),
            $now->addDays(2)->translatedFormat('l d \d\e F \d\e Y'),
            $now->addWeek()->translatedFormat('l d \d\e F \d\e Y'),
            $now->addMonth()->translatedFormat('l d \d\e F \d\e Y'),
            $now->year
        );
    }

    /**
     * Construir contexto para conversaciones almacenadas en Juntify
     * (sin usar AssistantConversation model local)
     */
    public function buildForJuntify(User $user, array $options = []): string
    {
        $meetingIds = Arr::wrap($options['meetings'] ?? []);
        $containerIds = Arr::wrap($options['containers'] ?? []);
        $includeCalendar = (bool) ($options['include_calendar'] ?? true);
        $userMessage = $options['user_message'] ?? null;

        Log::info('AssistantContextBuilder::buildForJuntify called', [
            'user_id' => $user->id,
            'meetingIds' => $meetingIds,
            'containerIds' => $containerIds,
            'includeCalendar' => $includeCalendar,
        ]);

        $contextParts = [];

        // Incluir información de fecha y hora actual SIEMPRE
        $contextParts[] = $this->buildCurrentDateTimeContext();

        if (!empty($meetingIds)) {
            $contextParts[] = $this->buildMeetingsContext($user, $meetingIds, $userMessage);
        }

        if (!empty($containerIds)) {
            $contextParts[] = $this->buildContainersContext($user, $containerIds);
        }

        if ($includeCalendar) {
            $contextParts[] = $this->buildCalendarContext($user);
        }

        $contextParts = array_filter($contextParts);

        return implode("\n\n", $contextParts);
    }
}
