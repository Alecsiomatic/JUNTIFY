<?php

namespace App\Http\Controllers;

use App\Services\Assistant\AssistantService;
use App\Services\Assistant\DocumentParser;
use App\Services\Assistant\JuntifyConversationAdapter;
use App\Services\Assistant\OpenAiClient;
use App\Services\Calendar\GoogleCalendarService;
use App\Services\JuntifyApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssistantController extends Controller
{
    public function __construct(
        private readonly AssistantService $assistantService,
        private readonly DocumentParser $documentParser,
        private readonly OpenAiClient $openAiClient,
        private readonly GoogleCalendarService $calendarService,
        private readonly JuntifyApiService $juntifyApi,
        private readonly JuntifyConversationAdapter $conversationAdapter,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        // Obtener configuración desde Juntify API
        $settingsResult = $this->juntifyApi->getAssistantSettings($user->id);
        
        if ($settingsResult['success'] && $settingsResult['data']) {
            $settings = (object) $settingsResult['data'];
        } else {
            // Valores por defecto si no hay configuración
            $settings = (object) [
                'enable_drive_calendar' => true,
                'openai_api_key_configured' => false,
            ];
        }

        // Obtener conversaciones desde Juntify API
        $conversations = $this->conversationAdapter->getConversations($user->id);

        $activeConversation = $conversations->first();

        if ($activeConversation) {
            // Cargar la conversación completa con mensajes y documentos
            $activeConversation = $this->conversationAdapter->getConversation($activeConversation->id, $user->id);
        }

        // Obtener reuniones propias del usuario desde Juntify API
        $meetingsResult = $this->juntifyApi->getUserMeetings($user->id, [
            'limit' => 50,
            'order_by' => 'created_at',
            'order_dir' => 'desc'
        ]);
        
        $ownMeetings = collect();
        if ($meetingsResult['success'] && isset($meetingsResult['data']['meetings'])) {
            $ownMeetings = collect($meetingsResult['data']['meetings'])->map(function ($meeting) {
                return (object) [
                    'id' => $meeting['id'],
                    'meeting_name' => $meeting['meeting_name'] ?? $meeting['name'] ?? 'Sin nombre',
                    'created_at' => isset($meeting['created_at']) ? Carbon::parse($meeting['created_at']) : null,
                    'transcript_drive_id' => $meeting['transcript_drive_id'] ?? null,
                    'source' => 'own',
                    'source_label' => 'Propia',
                ];
            });
        }

        // Obtener reuniones compartidas en grupos
        $sharedMeetings = collect();
        $groupsResult = $this->juntifyApi->getUserMeetingGroups($user->id, false, false);
        
        if ($groupsResult['success'] && !empty($groupsResult['data']['groups'])) {
            foreach ($groupsResult['data']['groups'] as $group) {
                $groupMeetingsResult = $this->juntifyApi->getGroupSharedMeetings($group['id']);
                
                if ($groupMeetingsResult['success'] && !empty($groupMeetingsResult['data']['meetings'])) {
                    foreach ($groupMeetingsResult['data']['meetings'] as $meeting) {
                        // Evitar duplicados si la reunión ya está en las propias
                        $meetingId = $meeting['id'] ?? $meeting['meeting_id'] ?? null;
                        if ($meetingId && !$ownMeetings->contains('id', $meetingId) && !$sharedMeetings->contains('id', $meetingId)) {
                            $sharedMeetings->push((object) [
                                'id' => $meetingId,
                                'meeting_name' => $meeting['meeting_name'] ?? $meeting['name'] ?? 'Sin nombre',
                                'created_at' => isset($meeting['created_at']) ? Carbon::parse($meeting['created_at']) : null,
                                'transcript_drive_id' => $meeting['transcript_drive_id'] ?? null,
                                'source' => 'group',
                                'source_label' => 'Grupo: ' . ($group['name'] ?? 'Sin nombre'),
                                'group_id' => $group['id'],
                                'group_name' => $group['name'] ?? 'Sin nombre',
                            ]);
                        }
                    }
                }
            }
        }

        // Combinar reuniones propias y compartidas
        $meetings = $ownMeetings->concat($sharedMeetings)->sortByDesc('created_at')->take(50)->values();

        // Obtener contenedores desde Juntify API (si hay endpoint disponible)
        // Por ahora dejamos vacío ya que el foco principal son las reuniones
        $containers = collect();

        $calendarEvents = collect();

        $enableDriveCalendar = $settings->enable_drive_calendar ?? true;
        
        if ($enableDriveCalendar && $user->googleToken) {
            try {
                $calendarEvents = $this->calendarService->listUpcomingEvents($user, Carbon::now(), Carbon::now()->addWeeks(2));
            } catch (\Throwable $exception) {
                $calendarEvents = collect();
            }
        }

        // Verificar si tiene API key configurada
        $isConfigured = $settings->openai_api_key_configured ?? false;

        return view('dashboard.asistente.index', [
            'settings' => $settings,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'meetings' => $meetings,
            'containers' => $containers,
            'calendarEvents' => $calendarEvents,
            'apiConnected' => $isConfigured,
        ]);
    }

    public function createConversation(Request $request)
    {
        $user = $request->user();
        $title = $request->input('title', 'Nueva conversación');
        
        // Crear conversación en Juntify
        $conversation = $this->conversationAdapter->createConversation($user->id, $title);
        
        if (!$conversation) {
            return response()->json(['error' => 'No se pudo crear la conversación'], 500);
        }

        // Agregar mensaje de sistema y bienvenida
        $this->conversationAdapter->addMessage(
            $conversation->id,
            $user->id,
            'system',
            'Eres el asistente inteligente de DDU. Usa exclusivamente el contexto de reuniones, documentos y eventos del calendario proporcionados. Mantén el contexto de la conversación y ofrece respuestas en español. Si no tienes datos suficientes, indícalo.'
        );

        $this->conversationAdapter->addMessage(
            $conversation->id,
            $user->id,
            'assistant',
            'Hola, soy tu asistente DDU. Puedo apoyarte con tus reuniones, documentos y eventos de calendario usando el contexto que selecciones. ¿En qué puedo ayudarte hoy?'
        );

        // Recargar conversación con mensajes
        $conversation = $this->conversationAdapter->getConversation($conversation->id, $user->id);

        return response()->json([
            'conversation' => $conversation,
        ]);
    }

    public function showConversation(Request $request, int $conversationId)
    {
        $user = $request->user();
        $conversation = $this->conversationAdapter->getConversation($conversationId, $user->id);

        if (!$conversation) {
            abort(404, 'Conversación no encontrada');
        }

        return response()->json([
            'conversation' => $conversation,
        ]);
    }

    public function deleteConversation(Request $request, int $conversationId): Response
    {
        $user = $request->user();
        $deleted = $this->conversationAdapter->deleteConversation($conversationId, $user->id);

        if (!$deleted) {
            return response()->json(['error' => 'No se pudo eliminar la conversación'], 500);
        }

        return response()->noContent();
    }

    public function updateConversation(Request $request, int $conversationId): Response
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $updated = $this->conversationAdapter->updateConversation($conversationId, $user->id, [
            'title' => $validated['title']
        ]);

        if (!$updated) {
            return response()->json(['error' => 'No se pudo actualizar la conversación'], 500);
        }

        $conversation = $this->conversationAdapter->getConversation($conversationId, $user->id);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'conversation_id' => ['nullable', 'integer'],
            'meetings' => ['array'],
            'meetings.*' => ['integer'],
            'containers' => ['array'],
            'containers.*' => ['integer'],
        ]);

        $user = $request->user();
        
        // Verificar si tiene API key configurada en Juntify
        if (!$this->openAiClient->isConfiguredForUser($user->id)) {
            return response()->json([
                'error' => 'Configura la API key de OpenAI en la sección de configuración del asistente antes de continuar.',
            ], 422);
        }
        
        // Obtener configuración desde Juntify API
        $settingsResult = $this->juntifyApi->getAssistantSettings($user->id);
        $settings = $settingsResult['success'] && $settingsResult['data'] 
            ? (object) $settingsResult['data'] 
            : (object) ['enable_drive_calendar' => true];

        // Obtener o crear conversación usando Juntify API
        $conversationId = $validated['conversation_id'] ?? null;
        
        if ($conversationId) {
            $conversation = $this->conversationAdapter->getConversation($conversationId, $user->id);
            if (!$conversation) {
                return response()->json(['error' => 'Conversación no encontrada'], 404);
            }
        } else {
            $conversation = $this->conversationAdapter->createConversation($user->id, 'Nueva conversación');
            if (!$conversation) {
                return response()->json(['error' => 'No se pudo crear la conversación'], 500);
            }
            
            // Agregar mensajes iniciales
            $this->conversationAdapter->addMessage(
                $conversation->id,
                $user->id,
                'system',
                'Eres el asistente inteligente de DDU. Usa exclusivamente el contexto de reuniones, documentos y eventos del calendario proporcionados. Mantén el contexto de la conversación y ofrece respuestas en español. Si no tienes datos suficientes, indícalo.'
            );
            
            $this->conversationAdapter->addMessage(
                $conversation->id,
                $user->id,
                'assistant',
                'Hola, soy tu asistente DDU. Puedo apoyarte con tus reuniones, documentos y eventos de calendario usando el contexto que selecciones. ¿En qué puedo ayudarte hoy?'
            );
        }

        // Registrar mensaje del usuario
        $this->conversationAdapter->addMessage(
            $conversation->id,
            $user->id,
            'user',
            $validated['message']
        );

        try {
            // Generar respuesta del asistente
            $reply = $this->assistantService->generateAssistantReplyWithJuntify(
                $user,
                $conversation->id,
                $settings,
                $validated['message'],
                [
                    'meetings' => $validated['meetings'] ?? [],
                    'containers' => $validated['containers'] ?? [],
                    'include_calendar' => $settings->enable_drive_calendar ?? true,
                ],
                $this->conversationAdapter
            );
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => 'No fue posible generar una respuesta con la IA. Inténtalo nuevamente en unos momentos.',
            ], 500);
        }

        // Recargar conversación con todos los mensajes
        $updatedConversation = $this->conversationAdapter->getConversation($conversation->id, $user->id);

        return response()->json([
            'conversation_id' => $conversation->id,
            'reply' => $reply,
            'messages' => $updatedConversation->messages ?? collect(),
        ]);
    }

    public function uploadDocument(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'document' => ['required', 'file', 'max:10240'],
        ]);

        $user = $request->user();
        $conversationId = $validated['conversation_id'];
        
        // Verificar que la conversación existe y pertenece al usuario
        $conversation = $this->conversationAdapter->getConversation($conversationId, $user->id);
        
        if (!$conversation) {
            return response()->json(['error' => 'Conversación no encontrada'], 404);
        }

        /** @var UploadedFile $file */
        $file = $validated['document'];

        // Extraer texto del documento
        $text = $this->documentParser->extractText($file);

        // Generar resumen si hay texto extraído y API está configurada
        $summary = null;
        
        if ($text && $this->openAiClient->isConfiguredForUser($user->id)) {
            try {
                $response = $this->openAiClient->createChatCompletionForUser($user->id, [
                    ['role' => 'system', 'content' => 'Eres un asistente que resume documentos de manera breve.'],
                    ['role' => 'user', 'content' => 'Resume el siguiente documento en español en máximo 6 oraciones destacando tema principal y puntos clave: ' . Str::limit($text, 6000)],
                ], ['temperature' => 0.2]);

                $summary = $this->openAiClient->extractMessageContent($response);
            } catch (\Throwable $e) {
                // Si falla el resumen, continuar sin él
                \Log::warning('No se pudo generar resumen del documento: ' . $e->getMessage());
            }
        }

        // Subir documento a Juntify
        $document = $this->conversationAdapter->uploadDocument(
            $conversationId,
            $user->id,
            $file,
            $text,
            $summary
        );

        if (!$document) {
            return response()->json(['error' => 'No se pudo subir el documento'], 500);
        }

        // Registrar el documento en la conversación como mensaje
        $this->conversationAdapter->addMessage(
            $conversationId,
            $user->id,
            'system',
            "[Documento adjunto: {$file->getClientOriginalName()}]\n\n" . 
            ($summary ? "Resumen: {$summary}" : "El documento ha sido cargado correctamente."),
            ['document_id' => $document->id]
        );

        return response()->json([
            'document' => $document,
            'summary' => $summary,
        ]);
    }

    protected function buildDocumentMetadata(UploadedFile $file): array
    {
        $metadata = [
            'extension' => $file->getClientOriginalExtension(),
        ];

        if (Str::startsWith($file->getMimeType(), 'image/')) {
            $metadata['image_preview'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }

        return $metadata;
    }
}
