<?php

namespace App\Services\Assistant;

use App\Services\JuntifyApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAiClient
{
    private Client $httpClient;
    private JuntifyApiService $juntifyApi;

    public function __construct(JuntifyApiService $juntifyApi)
    {
        $this->httpClient = new Client([
            'base_uri' => config('services.openai.base_uri', 'https://api.openai.com/v1/'),
            'timeout' => 120,
        ]);
        $this->juntifyApi = $juntifyApi;
    }

    /**
     * Verificar si el usuario tiene API key configurada
     */
    public function isConfiguredForUser(string $userId): bool
    {
        $result = $this->juntifyApi->getAssistantSettings($userId);
        return $result['success'] && ($result['data']['openai_api_key_configured'] ?? false);
    }

    /**
     * Obtener API key del usuario desde Juntify
     */
    public function getApiKeyForUser(string $userId): ?string
    {
        $result = $this->juntifyApi->getAssistantApiKey($userId);
        
        if (!$result['success']) {
            return null;
        }
        
        return $result['data']['openai_api_key'] ?? null;
    }

    /**
     * @deprecated Usar isConfiguredForUser() en su lugar
     */
    public function isConfigured($settings): bool
    {
        if (is_object($settings) && isset($settings->openai_api_key_configured)) {
            return (bool) $settings->openai_api_key_configured;
        }
        
        if (is_object($settings) && property_exists($settings, 'openai_api_key')) {
            return (bool) $settings->openai_api_key;
        }
        
        return false;
    }

    /**
     * Crear chat completion usando el user_id para obtener la API key
     */
    public function createChatCompletionForUser(string $userId, array $messages, array $options = []): array
    {
        $apiKey = $this->getApiKeyForUser($userId);
        
        if (!$apiKey) {
            throw new RuntimeException('El asistente no está configurado con una API key de OpenAI.');
        }

        return $this->createChatCompletionWithKey($apiKey, $messages, $options);
    }

    /**
     * Crear chat completion con una API key específica
     */
    public function createChatCompletionWithKey(string $apiKey, array $messages, array $options = []): array
    {
        $payload = array_merge([
            'model' => config('services.openai.model', 'gpt-4o-mini'),
            'messages' => $messages,
            'temperature' => 0.3,
        ], $options);

        try {
            $response = $this->httpClient->post('chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (GuzzleException $exception) {
            Log::error('Error comunicándose con OpenAI', [
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('No fue posible obtener una respuesta de OpenAI en este momento.');
        }

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data) || empty($data['choices'])) {
            Log::error('Respuesta inesperada de OpenAI', [
                'response' => $data,
            ]);

            throw new RuntimeException('OpenAI devolvió una respuesta inválida.');
        }

        return $data;
    }

    /**
     * @deprecated Usar createChatCompletionForUser() en su lugar
     */
    public function createChatCompletion($settings, array $messages, array $options = []): array
    {
        // Compatibilidad: si settings tiene openai_api_key directamente (legacy)
        if (is_object($settings) && property_exists($settings, 'openai_api_key') && $settings->openai_api_key) {
            return $this->createChatCompletionWithKey($settings->openai_api_key, $messages, $options);
        }
        
        throw new RuntimeException('El asistente no está configurado con una API key de OpenAI.');
    }

    public function extractMessageContent(array $response): string
    {
        $choice = Arr::first($response['choices'], fn ($choice) => isset($choice['message']['content']));

        return trim((string) ($choice['message']['content'] ?? ''));
    }

    public function extractToolCalls(array $response): Collection
    {
        $choice = Arr::first($response['choices']);
        $toolCalls = Arr::get($choice, 'message.tool_calls', []);

        return collect($toolCalls);
    }
}
