<?php

namespace App\Services;

use App\Models\GoogleToken;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class UserGoogleDriveService
{
    private GoogleToken $token;

    private Client $httpClient;

    private ?string $accessToken = null;

    private ?CarbonImmutable $accessTokenExpiresAt = null;

    public function __construct(GoogleToken $token)
    {
        $this->token = $token;
        $this->httpClient = new Client([
            'base_uri' => 'https://www.googleapis.com/',
            'timeout' => 60,
        ]);

        $this->initialiseAccessToken();
    }

    public function downloadFile(string $fileId): ?ResponseInterface
    {
        $this->ensureValidAccessToken();

        try {
            $response = $this->requestDriveFile($fileId);

            if ($response->getStatusCode() === 401) {
                $this->refreshAccessToken();
                $response = $this->requestDriveFile($fileId);
            }

            if ($response->getStatusCode() >= 400) {
                Log::error('Google Drive devolvió un error al descargar el archivo.', [
                    'file_id' => $fileId,
                    'status' => $response->getStatusCode(),
                    'user_id' => $this->token->user_id,
                ]);

                return null;
            }

            return $response;
        } catch (RequestException $exception) {
            $response = $exception->getResponse();

            Log::error('Error HTTP al intentar descargar un archivo de Google Drive.', [
                'file_id' => $fileId,
                'user_id' => $this->token->user_id,
                'status' => $response?->getStatusCode(),
                'message' => $exception->getMessage(),
            ]);
        } catch (GuzzleException $exception) {
            Log::error('Error inesperado al descargar un archivo de Google Drive.', [
                'file_id' => $fileId,
                'user_id' => $this->token->user_id,
                'message' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    public function getValidAccessToken(): string
    {
        $this->ensureValidAccessToken();

        if (! $this->accessToken) {
            throw new \RuntimeException('No hay un access token válido disponible.');
        }

        return $this->accessToken;
    }

    private function initialiseAccessToken(): void
    {
        $payload = $this->decodeStoredToken($this->token->access_token);
        $this->accessToken = Arr::get($payload, 'access_token');
        $this->accessTokenExpiresAt = $this->resolveExpiry($payload);

        if (! $this->accessToken || $this->tokenHasExpired()) {
            $this->refreshAccessToken();
        }
    }

    private function ensureValidAccessToken(): void
    {
        if (! $this->accessToken || $this->tokenHasExpired()) {
            $this->refreshAccessToken();
        }
    }

    private function tokenHasExpired(): bool
    {
        if ($this->accessTokenExpiresAt) {
            return $this->accessTokenExpiresAt->isPast();
        }

        if ($this->token->expiry_date) {
            return $this->token->expiry_date->isPast();
        }

        return false;
    }

    private function refreshAccessToken(): void
    {
        $config = config('services.google');
        $refreshToken = $this->resolveRefreshToken();

        if (! $config['client_id'] || ! $config['client_secret']) {
            throw new \RuntimeException('Las credenciales de Google OAuth no están configuradas.');
        }

        if (! $refreshToken) {
            throw new \RuntimeException('El token de Google no tiene refresh token asociado.');
        }

        try {
            $response = $this->httpClient->post($config['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'refresh_token' => $refreshToken,
                ],
            ]);
        } catch (GuzzleException $exception) {
            Log::error('No fue posible refrescar el token de Google.', [
                'user_id' => $this->token->user_id,
                'message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('No se pudo actualizar el token de Google.');
        }

        $payload = json_decode((string) $response->getBody(), true) ?: [];

        if (! isset($payload['access_token'])) {
            Log::error('Respuesta inesperada al refrescar el token de Google.', [
                'user_id' => $this->token->user_id,
                'payload' => $payload,
            ]);

            throw new \RuntimeException('Google no devolvió un access token válido.');
        }

        if (empty($payload['refresh_token'])) {
            $payload['refresh_token'] = $refreshToken;
        }

        $expiresAt = $this->determineExpiryFromPayload($payload);

        $this->token->forceFill([
            'access_token' => json_encode($payload),
            'refresh_token' => $payload['refresh_token'] ?? $refreshToken,
            'token_type' => $payload['token_type'] ?? $this->token->token_type,
            'scope' => $payload['scope'] ?? $this->token->scope,
            'token_created_at' => now(),
            'expiry_date' => $expiresAt ? $expiresAt->toDateTimeString() : null,
        ])->save();

        $this->accessToken = $payload['access_token'];
        $this->accessTokenExpiresAt = $expiresAt;
    }

    private function requestDriveFile(string $fileId): ResponseInterface
    {
        return $this->httpClient->request('GET', "drive/v3/files/{$fileId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],
            'query' => ['alt' => 'media'],
            'http_errors' => false,
            'stream' => true,
        ]);
    }

    private function resolveRefreshToken(): ?string
    {
        $stored = $this->decodeStoredToken($this->token->access_token);

        return $stored['refresh_token']
            ?? $this->token->refresh_token
            ?? null;
    }

    private function decodeStoredToken(?string $token): array
    {
        if (! $token) {
            return [];
        }

        $decoded = json_decode($token, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return [
            'access_token' => $token,
            'refresh_token' => $this->token->refresh_token,
        ];
    }

    private function resolveExpiry(array $payload): ?CarbonImmutable
    {
        if (isset($payload['expiry'])) {
            return CarbonImmutable::createFromTimestamp((int) $payload['expiry']);
        }

        if (isset($payload['expires_at'])) {
            return CarbonImmutable::createFromTimestamp((int) $payload['expires_at']);
        }

        if (isset($payload['created'], $payload['expires_in'])) {
            $created = (int) $payload['created'];
            $expiresIn = (int) $payload['expires_in'];

            return CarbonImmutable::createFromTimestamp($created + $expiresIn);
        }

        if ($this->token->expiry_date) {
            return CarbonImmutable::createFromTimestamp($this->token->expiry_date->getTimestamp());
        }

        return null;
    }

    private function determineExpiryFromPayload(array $payload): ?CarbonImmutable
    {
        if (isset($payload['expires_in'])) {
            return now()->addSeconds((int) $payload['expires_in'])->toImmutable();
        }

        if (isset($payload['expiry'])) {
            return CarbonImmutable::createFromTimestamp((int) $payload['expiry']);
        }

        if (isset($payload['expires_at'])) {
            return CarbonImmutable::createFromTimestamp((int) $payload['expires_at']);
        }

        return null;
    }
}
