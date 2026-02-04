<?php

namespace App\Services\Calendar;

use App\Models\GoogleToken;
use App\Models\User;
use App\Services\UserGoogleDriveService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleCalendarService
{
    private Client $httpClient;

    public function __construct(?Client $httpClient = null)
    {
        if ($httpClient) {
            // Si se pasa un cliente personalizado, asegurar que tenga base_uri configurado
            $config = $httpClient->getConfig();
            if (empty($config['base_uri'])) {
                $this->httpClient = new Client(array_merge($config, [
                    'base_uri' => 'https://www.googleapis.com/',
                    'timeout' => 60,
                ]));
            } else {
                $this->httpClient = $httpClient;
            }
        } else {
            // Cliente por defecto
            $this->httpClient = new Client([
                'base_uri' => 'https://www.googleapis.com/',
                'timeout' => 60,
            ]);
        }
    }

    protected function resolveToken(User $user): GoogleToken
    {
        $token = $user->googleToken;

        if (! $token) {
            throw new RuntimeException('No se encontró un token de Google vinculado al usuario.');
        }

        return $token;
    }

    protected function resolveAccessToken(GoogleToken $token): string
    {
        $service = new UserGoogleDriveService($token);

        return $service->getValidAccessToken();
    }

    public function createEvent(User $user, array $payload): array
    {
        $token = $this->resolveToken($user);
        $accessToken = $this->resolveAccessToken($token);

        $body = [
            'summary' => Arr::get($payload, 'summary'),
            'description' => Arr::get($payload, 'description'),
            'start' => [
                'dateTime' => Arr::get($payload, 'start'),
                'timeZone' => config('app.timezone', 'America/Mexico_City'),
            ],
            'end' => [
                'dateTime' => Arr::get($payload, 'end'),
                'timeZone' => config('app.timezone', 'America/Mexico_City'),
            ],
        ];

        $attendees = array_filter(Arr::get($payload, 'attendees', []));

        if (! empty($attendees)) {
            $body['attendees'] = array_map(fn ($email) => ['email' => $email], $attendees);
        }

        try {
            // Debug temporal: verificar la URL que se está usando
            Log::info('GoogleCalendarService: Intentando crear evento', [
                'url' => 'calendar/v3/calendars/primary/events',
                'base_uri' => $this->httpClient->getConfig('base_uri'),
                'user_id' => $user->id,
            ]);

            $response = $this->httpClient->post('calendar/v3/calendars/primary/events', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);
        } catch (RequestException $exception) {
            $logData = [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ];

            // Agregar respuesta si hay una respuesta HTTP
            if ($exception->getResponse()) {
                $logData['response'] = (string) $exception->getResponse()->getBody();
            }

            Log::error('Error al crear evento en Google Calendar.', $logData);

            throw new RuntimeException('No fue posible programar el evento en Google Calendar.');
        } catch (GuzzleException $exception) {
            Log::error('Error al crear evento en Google Calendar.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('No fue posible programar el evento en Google Calendar.');
        }

        return json_decode((string) $response->getBody(), true) ?: [];
    }

    public function listUpcomingEvents(User $user, Carbon $from, Carbon $to): Collection
    {
        $token = $this->resolveToken($user);
        $accessToken = $this->resolveAccessToken($token);

        $query = http_build_query([
            'timeMin' => $from->toIso8601String(),
            'timeMax' => $to->toIso8601String(),
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
        ]);

        try {
            // Debug temporal: verificar la URL que se está usando
            Log::info('GoogleCalendarService: Intentando obtener eventos', [
                'url' => 'calendar/v3/calendars/primary/events?' . $query,
                'base_uri' => $this->httpClient->getConfig('base_uri'),
                'user_id' => $user->id,
            ]);

            $response = $this->httpClient->get('calendar/v3/calendars/primary/events?' . $query, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);
        } catch (RequestException $exception) {
            $logData = [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ];

            // Agregar respuesta si hay una respuesta HTTP
            if ($exception->getResponse()) {
                $logData['response'] = (string) $exception->getResponse()->getBody();
            }

            Log::error('Error al obtener eventos de Google Calendar.', $logData);

            throw new RuntimeException('No fue posible consultar los eventos de Google Calendar.');
        } catch (GuzzleException $exception) {
            Log::error('Error al obtener eventos de Google Calendar.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('No fue posible consultar los eventos de Google Calendar.');
        }

        $data = json_decode((string) $response->getBody(), true) ?: [];

        $items = Arr::get($data, 'items', []);

        return collect($items)->map(function (array $item) {
            $start = Arr::get($item, 'start.dateTime') ?? Arr::get($item, 'start.date');
            $end = Arr::get($item, 'end.dateTime') ?? Arr::get($item, 'end.date');

            return [
                'id' => Arr::get($item, 'id'),
                'summary' => Arr::get($item, 'summary', 'Evento sin título'),
                'description' => Arr::get($item, 'description'),
                'start' => $start,
                'end' => $end,
            ];
        });
    }
}
