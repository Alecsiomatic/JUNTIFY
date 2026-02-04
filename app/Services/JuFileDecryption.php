<?php

namespace App\Services;

class JuFileDecryption
{
    /**
     * Extract meeting information from decrypted .ju data regardless of key naming.
     */
    public static function extractMeetingInfo(?array $data): array
    {
        if (! is_array($data)) {
            return [
                'summary' => 'Resumen no disponible.',
                'key_points' => [],
                'segments' => [],
            ];
        }

        $summary = self::firstNonEmptyString($data, [
            'summary',
            'resumen',
            'overview',
            'descripcion',
            'description',
            'synopsis',
        ]);

        $keyPoints = self::resolveItems($data, [
            'key_points',
            'keyPoints',
            'puntos_clave',
            'puntosClave',
            'highlights',
            'takeaways',
            'bullet_points',
            'bulletPoints',
            'puntos_importantes',
            'puntosImportantes',
        ]);

        $segments = self::resolveArray($data, [
            'segments',
            'segmentos',
            'transcription',
            'transcriptions',
            'sections',
            'partes',
            'detalles',
        ]);

        return [
            'summary' => $summary ?? 'Resumen no disponible.',
            'key_points' => $keyPoints,
            'segments' => $segments,
        ];
    }

    /**
     * Resolve the first non-empty string value from the provided keys.
     */
    private static function firstNonEmptyString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Resolve an array of string items (e.g. key points) from the provided keys.
     */
    private static function resolveItems(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (is_array($value)) {
                return self::normaliseArrayValues($value);
            }

            if (is_string($value) && trim($value) !== '') {
                return self::splitStringItems($value);
            }
        }

        return [];
    }

    /**
     * Resolve a normalised array from the provided keys.
     */
    private static function resolveArray(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (is_array($value)) {
                return array_values($value);
            }
        }

        return [];
    }

    /**
     * Normalise arrays to sequential string values.
     */
    private static function normaliseArrayValues(array $items): array
    {
        $normalised = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $trimmed = trim($item);
                if ($trimmed !== '') {
                    $normalised[] = $trimmed;
                }
                continue;
            }

            if (is_array($item)) {
                $flattened = self::normaliseArrayValues($item);
                if (! empty($flattened)) {
                    $normalised = array_merge($normalised, $flattened);
                }
                continue;
            }

            if (is_scalar($item)) {
                $normalised[] = (string) $item;
            }
        }

        return $normalised;
    }

    /**
     * Split a string into bullet-like items.
     */
    private static function splitStringItems(string $value): array
    {
        $delimiters = "\n|\r|•|\u2022|- |• |\*";
        $parts = preg_split("/{$delimiters}/u", $value) ?: [];

        $items = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed !== '') {
                $items[] = $trimmed;
            }
        }

        if (empty($items)) {
            return [trim($value)];
        }

        return $items;
    }
}
