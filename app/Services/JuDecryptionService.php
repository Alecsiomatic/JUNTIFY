<?php

namespace App\Services;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JuDecryptionService
{
    /**
     * Desencripta el contenido de un archivo .ju.
     */
    public static function decrypt(string $filePath): ?array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            Log::error("Archivo .ju no encontrado o no legible: {$filePath}");
            return null;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            Log::error("No se pudo leer el archivo .ju: {$filePath}");
            return null;
        }

        $jsonData = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            Log::info('El contenido del archivo .ju ya es JSON válido (sin encriptar).');
            return $jsonData;
        }

        try {
            $decryptedJson = Crypt::decryptString($content);
            $decoded = json_decode($decryptedJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        } catch (\Throwable $e) {
            Log::warning('Falló la desencriptación con la APP_KEY actual. Intentando con claves legacy.', [
                'error' => $e->getMessage(),
            ]);
        }

        $legacyKeys = array_filter(array_map('trim', explode(',', (string) env('LEGACY_APP_KEYS', ''))));
        $cipher = config('app.cipher', 'AES-256-CBC');

        foreach ($legacyKeys as $legacyKey) {
            try {
                $rawKey = self::resolveKey($legacyKey);
                if (!$rawKey) {
                    continue;
                }

                $encrypter = new Encrypter($rawKey, $cipher);
                $decryptedJson = $encrypter->decryptString($content);
                $decoded = json_decode($decryptedJson, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        Log::error("Todas las claves (actual y legacy) fallaron al intentar desencriptar el archivo: {$filePath}");
        return null;
    }

    /**
     * Desencripta el contenido de un archivo .ju proporcionado como cadena.
     */
    public static function decryptContent(string $content): ?array
    {
        $jsonData = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            Log::info('El contenido proporcionado ya es JSON válido (sin encriptar).');
            return $jsonData;
        }

        try {
            $decryptedJson = Crypt::decryptString($content);
            $decoded = json_decode($decryptedJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        } catch (\Throwable $e) {
            Log::warning('Falló la desencriptación del contenido con la APP_KEY actual. Intentando con claves legacy.', [
                'error' => $e->getMessage(),
            ]);
        }

        $legacyKeys = array_filter(array_map('trim', explode(',', (string) env('LEGACY_APP_KEYS', ''))));
        $cipher = config('app.cipher', 'AES-256-CBC');

        foreach ($legacyKeys as $legacyKey) {
            try {
                $rawKey = self::resolveKey($legacyKey);
                if (!$rawKey) {
                    continue;
                }

                $encrypter = new Encrypter($rawKey, $cipher);
                $decryptedJson = $encrypter->decryptString($content);
                $decoded = json_decode($decryptedJson, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        Log::error('Todas las claves (actual y legacy) fallaron al intentar desencriptar el contenido proporcionado.');
        return null;
    }

    private static function resolveKey(string $key): ?string
    {
        $key = trim($key);

        if ($key === '') {
            return null;
        }

        if (Str::startsWith($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            return $decoded !== false ? $decoded : null;
        }

        return $key;
    }
}
