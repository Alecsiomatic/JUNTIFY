<?php

namespace App\Services\Assistant;

use App\Services\JuntifyApiService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Adaptador que permite trabajar con conversaciones almacenadas en Juntify API
 * como si fueran objetos locales, manteniendo compatibilidad con el código existente.
 */
class JuntifyConversationAdapter
{
    public function __construct(
        private readonly JuntifyApiService $juntifyApi
    ) {
    }

    /**
     * Obtener todas las conversaciones del usuario
     */
    public function getConversations(string $userId): Collection
    {
        $result = $this->juntifyApi->listConversations($userId);

        if (!$result['success']) {
            Log::error('Error al obtener conversaciones de Juntify', ['error' => $result['error'] ?? 'Unknown']);
            return collect();
        }

        return collect($result['data'])->map(function ($item) {
            return $this->mapToConversationObject($item);
        });
    }

    /**
     * Crear una nueva conversación
     */
    public function createConversation(string $userId, string $title = 'Nueva conversación'): ?object
    {
        $result = $this->juntifyApi->createConversation($userId, $title);

        if (!$result['success']) {
            Log::error('Error al crear conversación en Juntify', ['error' => $result['error'] ?? 'Unknown']);
            return null;
        }

        return $this->mapToConversationObject($result['data']);
    }

    /**
     * Obtener una conversación con sus mensajes
     */
    public function getConversation(int $conversationId, string $userId): ?object
    {
        $result = $this->juntifyApi->getConversation($conversationId, $userId);

        if (!$result['success']) {
            Log::error('Error al obtener conversación de Juntify', ['error' => $result['error'] ?? 'Unknown']);
            return null;
        }

        return $this->mapToConversationObject($result['data']);
    }

    /**
     * Actualizar una conversación
     */
    public function updateConversation(int $conversationId, string $userId, array $data): bool
    {
        $result = $this->juntifyApi->updateConversation($conversationId, $userId, $data);

        if (!$result['success']) {
            Log::error('Error al actualizar conversación en Juntify', ['error' => $result['error'] ?? 'Unknown']);
            return false;
        }

        return true;
    }

    /**
     * Eliminar una conversación
     */
    public function deleteConversation(int $conversationId, string $userId): bool
    {
        $result = $this->juntifyApi->deleteConversation($conversationId, $userId);

        if (!$result['success']) {
            Log::error('Error al eliminar conversación en Juntify', ['error' => $result['error'] ?? 'Unknown']);
            return false;
        }

        return true;
    }

    /**
     * Agregar mensaje a una conversación
     */
    public function addMessage(int $conversationId, string $userId, string $role, string $content, ?array $metadata = null): ?object
    {
        $result = $this->juntifyApi->addMessage($conversationId, $userId, $role, $content, $metadata);

        if (!$result['success']) {
            Log::error('Error al agregar mensaje en Juntify', ['error' => $result['error'] ?? 'Unknown']);
            return null;
        }

        return $this->mapToMessageObject($result['data']);
    }

    /**
     * Obtener mensajes de una conversación
     */
    public function getMessages(int $conversationId, string $userId, int $limit = 100, int $offset = 0): Collection
    {
        $result = $this->juntifyApi->getMessages($conversationId, $userId, $limit, $offset);

        if (!$result['success']) {
            Log::error('Error al obtener mensajes de Juntify', ['error' => $result['error'] ?? 'Unknown']);
            return collect();
        }

        return collect($result['data'])->map(function ($item) {
            return $this->mapToMessageObject($item);
        });
    }

    /**
     * Obtener documentos de una conversación
     */
    public function getDocuments(int $conversationId, string $userId): Collection
    {
        $result = $this->juntifyApi->getDocuments($conversationId, $userId);

        if (!$result['success']) {
            Log::error('Error al obtener documentos de Juntify', ['error' => $result['error'] ?? 'Unknown']);
            return collect();
        }

        return collect($result['data'])->map(function ($item) {
            return $this->mapToDocumentObject($item);
        });
    }

    /**
     * Subir documento a una conversación
     */
    public function uploadDocument(int $conversationId, string $userId, $file, ?string $extractedText = null, ?string $summary = null): ?object
    {
        $result = $this->juntifyApi->uploadDocument($conversationId, $userId, $file, $extractedText, $summary);

        if (!$result['success']) {
            Log::error('Error al subir documento en Juntify', ['error' => $result['error'] ?? 'Unknown']);
            return null;
        }

        return $this->mapToDocumentObject($result['data']);
    }

    /**
     * Eliminar documento de una conversación
     */
    public function deleteDocument(int $conversationId, int $documentId, string $userId): bool
    {
        $result = $this->juntifyApi->deleteDocument($conversationId, $documentId, $userId);

        if (!$result['success']) {
            Log::error('Error al eliminar documento en Juntify', ['error' => $result['error'] ?? 'Unknown']);
            return false;
        }

        return true;
    }

    /**
     * Mapear respuesta de API a objeto de conversación
     */
    protected function mapToConversationObject(array $data): object
    {
        $conversation = new \stdClass();
        $conversation->id = $data['id'] ?? null;
        $conversation->user_id = $data['user_id'] ?? null;
        $conversation->title = $data['title'] ?? 'Sin título';
        $conversation->description = $data['description'] ?? null;
        $conversation->messages_count = $data['messages_count'] ?? 0;
        $conversation->created_at = isset($data['created_at']) ? Carbon::parse($data['created_at']) : null;
        $conversation->updated_at = isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : null;

        // Mapear mensajes si existen
        if (isset($data['messages']) && is_array($data['messages'])) {
            $conversation->messages = collect($data['messages'])->map(fn($msg) => $this->mapToMessageObject($msg));
        } else {
            $conversation->messages = collect();
        }

        // Mapear documentos si existen
        if (isset($data['documents']) && is_array($data['documents'])) {
            $conversation->documents = collect($data['documents'])->map(fn($doc) => $this->mapToDocumentObject($doc));
        } else {
            $conversation->documents = collect();
        }

        return $conversation;
    }

    /**
     * Mapear respuesta de API a objeto de mensaje
     */
    protected function mapToMessageObject(array $data): object
    {
        $message = new \stdClass();
        $message->id = $data['id'] ?? null;
        $message->assistant_conversation_id = $data['assistant_conversation_id'] ?? $data['conversation_id'] ?? null;
        $message->role = $data['role'] ?? 'user';
        $message->content = $data['content'] ?? '';
        $message->metadata = $data['metadata'] ?? null;
        $message->created_at = isset($data['created_at']) ? Carbon::parse($data['created_at']) : null;
        $message->updated_at = isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : null;

        return $message;
    }

    /**
     * Mapear respuesta de API a objeto de documento
     */
    protected function mapToDocumentObject(array $data): object
    {
        $document = new \stdClass();
        $document->id = $data['id'] ?? null;
        $document->assistant_conversation_id = $data['assistant_conversation_id'] ?? $data['conversation_id'] ?? null;
        $document->original_name = $data['original_name'] ?? $data['filename'] ?? 'documento';
        $document->path = $data['path'] ?? $data['file_path'] ?? null;
        $document->mime_type = $data['mime_type'] ?? null;
        $document->size = $data['size'] ?? $data['file_size'] ?? 0;
        $document->extracted_text = $data['extracted_text'] ?? null;
        $document->summary = $data['summary'] ?? null;
        $document->metadata = $data['metadata'] ?? null;
        $document->created_at = isset($data['created_at']) ? Carbon::parse($data['created_at']) : null;
        $document->updated_at = isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : null;

        return $document;
    }
}
