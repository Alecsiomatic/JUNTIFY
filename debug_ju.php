<?php

require_once 'bootstrap/app.php';

use App\Services\JuDecryptionService;

$filePath = storage_path('app/Kualifin_Test.ju');
$data = JuDecryptionService::decrypt($filePath);

if ($data) {
    echo "Estructura del archivo .ju desencriptado:\n";
    echo "Claves disponibles: " . implode(', ', array_keys($data)) . "\n\n";

    if (isset($data['key_points'])) {
        echo "Key points encontrados:\n";
        var_dump($data['key_points']);
    } else {
        echo "No se encontraron 'key_points' en los datos.\n";
        echo "Buscando claves similares...\n";
        foreach (array_keys($data) as $key) {
            if (stripos($key, 'key') !== false || stripos($key, 'point') !== false || stripos($key, 'clave') !== false) {
                echo "Clave similar encontrada: $key\n";
                var_dump($data[$key]);
            }
        }
    }

    echo "\n\nDatos completos:\n";
    print_r($data);
} else {
    echo "No se pudo desencriptar el archivo.\n";
}
