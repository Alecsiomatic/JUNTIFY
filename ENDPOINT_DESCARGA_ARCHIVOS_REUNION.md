# 📥 ENDPOINT - Descarga de Archivos de Reunión

## Solicitud a Juntify

**Fecha:** 02/02/2026  
**Solicitado por:** Panel DDU  
**Prioridad:** 🔴 ALTA

---

## Necesidad

El Panel DDU necesita descargar archivos de reuniones (.ju y audio) pero **NO debe manejar los tokens de Google Drive directamente** por seguridad.

### Problema actual:
- ❌ Panel DDU no tiene acceso al Google Token del usuario
- ❌ Manejar tokens de Google Drive en Panel DDU es inseguro
- ❌ Panel DDU no debería tener acceso a Google Drive API

### Solución propuesta:
✅ **Juntify maneja la descarga completa**:
1. Panel DDU solicita archivo con meeting_id y username
2. Juntify busca el Google Token del usuario en su BD
3. Juntify descarga el archivo desde Google Drive
4. Juntify envía el archivo al Panel DDU (base64 o URL temporal)

---

## Endpoint Requerido

### **GET** `/api/meetings/{meeting_id}/download/{file_type}`

**Descripción:** Descargar archivo de reunión (.ju transcripción o audio). Juntify maneja el token y descarga desde Google Drive.

---

## Parámetros

### Path Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `meeting_id` | integer | ✅ Sí | ID de la reunión en `transcriptions_laravel` |
| `file_type` | string | ✅ Sí | Tipo de archivo: `transcript` o `audio` |

### Query Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `username` | string | ✅ Sí | Username del dueño de la reunión (para buscar su token) |
| `format` | string | ❌ No | Formato respuesta: `base64`, `url`, `stream`. Default: `base64` |

---

## Respuesta Esperada

### Success Response (200 OK) - Formato Base64

```json
{
  "success": true,
  "meeting_id": 5,
  "file_type": "transcript",
  "file_name": "reunion_2026_02_02.ju",
  "file_size_bytes": 524288,
  "file_size_mb": 0.5,
  "mime_type": "application/octet-stream",
  "file_content": "base64EncodedContentHere...",
  "encoding": "base64",
  "downloaded_at": "2026-02-02T18:30:00.000000Z"
}
```

### Success Response (200 OK) - Formato URL Temporal

```json
{
  "success": true,
  "meeting_id": 5,
  "file_type": "audio",
  "file_name": "audio_reunion.mp3",
  "download_url": "https://drive.google.com/uc?export=download&id=...",
  "expires_at": "2026-02-02T19:30:00.000000Z",
  "file_size_mb": 12.5
}
```

### Success Response (200 OK) - Formato Stream

```
Content-Type: application/octet-stream
Content-Disposition: attachment; filename="reunion.ju"

[Binary file content here]
```

---

### Error Response (404 Not Found)

```json
{
  "success": false,
  "message": "Reunión no encontrada",
  "meeting_id": 999
}
```

### Error Response (404 Not Found - Token)

```json
{
  "success": false,
  "message": "Google Token no encontrado para el usuario",
  "username": "usuario_sin_token"
}
```

### Error Response (404 Not Found - Archivo)

```json
{
  "success": false,
  "message": "Archivo no encontrado en Google Drive",
  "file_type": "transcript",
  "drive_id": "1ABC123..."
}
```

### Error Response (403 Forbidden)

```json
{
  "success": false,
  "message": "No tienes permisos para descargar este archivo",
  "meeting_id": 5,
  "username": "otro_usuario"
}
```

---

## Implementación Sugerida en Juntify

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeetingDownloadController extends Controller
{
    /**
     * Descargar archivo de reunión (.ju o audio)
     * Juntify maneja el token y descarga desde Google Drive
     */
    public function downloadFile(Request $request, int $meetingId, string $fileType): JsonResponse
    {
        try {
            // Validar file_type
            if (!in_array($fileType, ['transcript', 'audio'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de archivo inválido. Usar: transcript o audio',
                    'file_type' => $fileType
                ], 400);
            }

            // Obtener username del query
            $username = $request->query('username');
            if (!$username) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro username es requerido'
                ], 400);
            }

            // Buscar la reunión
            $meeting = DB::connection('juntify')
                ->table('transcriptions_laravel')
                ->where('id', $meetingId)
                ->where('username', $username) // Verificar que pertenece al usuario
                ->first();

            if (!$meeting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reunión no encontrada o no pertenece al usuario',
                    'meeting_id' => $meetingId,
                    'username' => $username
                ], 404);
            }

            // Obtener el Google Drive ID según el tipo
            $driveId = $fileType === 'transcript' 
                ? $meeting->transcript_drive_id 
                : $meeting->audio_drive_id;

            if (!$driveId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no disponible',
                    'file_type' => $fileType
                ], 404);
            }

            // Buscar el usuario para obtener su Google Token
            $user = DB::connection('juntify')
                ->table('users')
                ->where('username', $username)
                ->first(['id']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'username' => $username
                ], 404);
            }

            // Buscar el Google Token del usuario en juntify_panels
            $googleToken = DB::connection('juntify_panels')
                ->table('google_tokens')
                ->where('user_id', $user->id)
                ->first();

            if (!$googleToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Token no encontrado para el usuario',
                    'username' => $username,
                    'suggestion' => 'El usuario debe conectar su cuenta de Google Drive'
                ], 404);
            }

            // Verificar si el token está expirado y refrescarlo si es necesario
            $accessToken = $googleToken->access_token;
            
            if ($this->isTokenExpired($googleToken)) {
                $accessToken = $this->refreshGoogleToken($googleToken);
                
                if (!$accessToken) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al refrescar el token de Google',
                        'suggestion' => 'El usuario debe reconectar su cuenta de Google Drive'
                    ], 401);
                }
            }

            // Determinar formato de respuesta
            $format = $request->query('format', 'base64');

            if ($format === 'url') {
                // Retornar URL de descarga directa de Google Drive
                return $this->returnDownloadUrl($driveId, $meeting, $fileType);
            }

            // Descargar archivo desde Google Drive
            $fileContent = $this->downloadFromGoogleDrive($driveId, $accessToken);

            if (!$fileContent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al descargar archivo desde Google Drive',
                    'drive_id' => $driveId
                ], 500);
            }

            if ($format === 'stream') {
                // Retornar archivo como stream
                return response($fileContent)
                    ->header('Content-Type', 'application/octet-stream')
                    ->header('Content-Disposition', 'attachment; filename="' . $this->getFileName($meeting, $fileType) . '"');
            }

            // Retornar archivo en base64 (default)
            return response()->json([
                'success' => true,
                'meeting_id' => $meetingId,
                'file_type' => $fileType,
                'file_name' => $this->getFileName($meeting, $fileType),
                'file_size_bytes' => strlen($fileContent),
                'file_size_mb' => round(strlen($fileContent) / 1048576, 2),
                'mime_type' => $this->getMimeType($fileType),
                'file_content' => base64_encode($fileContent),
                'encoding' => 'base64',
                'downloaded_at' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error('Error al descargar archivo de reunión', [
                'meeting_id' => $meetingId,
                'file_type' => $fileType,
                'username' => $username ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la descarga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si el token está expirado
     */
    protected function isTokenExpired($googleToken): bool
    {
        if (!$googleToken->expires_at) {
            return true;
        }

        return now()->greaterThan($googleToken->expires_at);
    }

    /**
     * Refrescar el token de Google
     */
    protected function refreshGoogleToken($googleToken): ?string
    {
        try {
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $googleToken->refresh_token,
                'grant_type' => 'refresh_token'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $newAccessToken = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;

                // Actualizar token en la base de datos
                DB::connection('juntify_panels')
                    ->table('google_tokens')
                    ->where('id', $googleToken->id)
                    ->update([
                        'access_token' => $newAccessToken,
                        'expires_at' => now()->addSeconds($expiresIn)
                    ]);

                return $newAccessToken;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error al refrescar token de Google', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Descargar archivo desde Google Drive
     */
    protected function downloadFromGoogleDrive(string $driveId, string $accessToken): ?string
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("https://www.googleapis.com/drive/v3/files/{$driveId}", [
                    'alt' => 'media'
                ]);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('Error al descargar desde Google Drive', [
                'drive_id' => $driveId,
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Excepción al descargar desde Google Drive', [
                'drive_id' => $driveId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Retornar URL de descarga directa
     */
    protected function returnDownloadUrl(string $driveId, $meeting, string $fileType): JsonResponse
    {
        $downloadUrl = "https://drive.google.com/uc?export=download&id={$driveId}";

        return response()->json([
            'success' => true,
            'meeting_id' => $meeting->id,
            'file_type' => $fileType,
            'file_name' => $this->getFileName($meeting, $fileType),
            'download_url' => $downloadUrl,
            'drive_id' => $driveId,
            'note' => 'URL requiere acceso a Google Drive del usuario'
        ]);
    }

    /**
     * Obtener nombre de archivo
     */
    protected function getFileName($meeting, string $fileType): string
    {
        $name = str_replace(' ', '_', $meeting->meeting_name);
        $extension = $fileType === 'transcript' ? 'ju' : 'mp3';
        
        return "{$name}_{$meeting->id}.{$extension}";
    }

    /**
     * Obtener MIME type
     */
    protected function getMimeType(string $fileType): string
    {
        return $fileType === 'transcript' 
            ? 'application/octet-stream' 
            : 'audio/mpeg';
    }
}
```

---

## Registro de Rutas en Juntify

### `routes/api.php`

```php
use App\Http\Controllers\Api\MeetingDownloadController;

// Descargar archivos de reunión
Route::get('/meetings/{meeting_id}/download/{file_type}', [MeetingDownloadController::class, 'downloadFile'])
    ->where('file_type', 'transcript|audio');
```

---

## Ejemplos de Uso

### 1. Descargar transcripción (.ju) en base64

```bash
GET http://127.0.0.1:8000/api/meetings/5/download/transcript?username=Jona0327
```

**PowerShell:**
```powershell
$meetingId = 5
$username = "Jona0327"
$response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/meetings/$meetingId/download/transcript?username=$username"

# Guardar archivo
$bytes = [Convert]::FromBase64String($response.file_content)
[IO.File]::WriteAllBytes("C:\Downloads\reunion.ju", $bytes)
```

### 2. Descargar audio en base64

```bash
GET http://127.0.0.1:8000/api/meetings/5/download/audio?username=Jona0327
```

**PowerShell:**
```powershell
$response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/meetings/5/download/audio?username=Jona0327"

# Guardar audio
$bytes = [Convert]::FromBase64String($response.file_content)
[IO.File]::WriteAllBytes("C:\Downloads\audio.mp3", $bytes)
```

### 3. Obtener URL de descarga directa

```bash
GET http://127.0.0.1:8000/api/meetings/5/download/transcript?username=Jona0327&format=url
```

**PowerShell:**
```powershell
$response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/meetings/5/download/transcript?username=Jona0327&format=url"
Write-Output $response.download_url
```

### 4. Descargar como stream (archivo directo)

```bash
GET http://127.0.0.1:8000/api/meetings/5/download/audio?username=Jona0327&format=stream
```

**PowerShell:**
```powershell
Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/meetings/5/download/audio?username=Jona0327&format=stream" `
    -OutFile "C:\Downloads\reunion_audio.mp3"
```

---

## Flujo de Seguridad

### ✅ Ventajas de este enfoque:

1. **Panel DDU nunca maneja tokens de Google**
   - No necesita acceso a `google_tokens`
   - No necesita credenciales de Google API
   
2. **Juntify controla el acceso**
   - Verifica que el usuario sea dueño de la reunión
   - Maneja refresh de tokens automáticamente
   - Centraliza la lógica de Google Drive

3. **Seguridad**
   - Validación de permisos en Juntify
   - Tokens nunca se exponen al Panel DDU
   - Control centralizado de acceso

4. **Simplicidad para Panel DDU**
   - Solo necesita meeting_id y username
   - Recibe archivo listo para usar
   - No requiere configuración de Google API

---

## Consideraciones Técnicas

### Límites de Tamaño

- **Base64**: Recomendado para archivos < 10MB
- **Stream**: Recomendado para archivos > 10MB
- **URL**: Para archivos muy grandes (permite descarga directa del cliente)

### Timeout

- Configurar timeout generoso (60+ segundos) para archivos grandes
- Panel DDU debe esperar la respuesta completa

### Caché (Opcional)

Juntify puede cachear archivos descargados temporalmente:
```php
$cacheKey = "meeting_file_{$meetingId}_{$fileType}";
$fileContent = Cache::remember($cacheKey, 3600, function() use ($driveId, $accessToken) {
    return $this->downloadFromGoogleDrive($driveId, $accessToken);
});
```

---

## Beneficios

✅ **Seguridad máxima:** Tokens de Google nunca salen de Juntify  
✅ **Simplicidad:** Panel DDU solo solicita archivo  
✅ **Control centralizado:** Juntify maneja permisos y acceso  
✅ **Mantenibilidad:** Cambios en Google API solo afectan a Juntify  
✅ **Escalabilidad:** Fácil agregar caché, CDN, etc.  
✅ **Flexibilidad:** 3 formatos de respuesta (base64, url, stream)  

---

## Archivos a Crear en Juntify

1. ✅ `app/Http/Controllers/Api/MeetingDownloadController.php` - Controlador nuevo
2. ✅ `routes/api.php` - Agregar ruta
3. ✅ Configurar Google API credentials en `.env`

---

## Verificación

Después de implementar, verificar que:

1. ✅ Descarga archivo .ju correctamente
2. ✅ Descarga audio correctamente
3. ✅ Verifica permisos (usuario debe ser dueño)
4. ✅ Maneja tokens expirados (refresh automático)
5. ✅ Retorna errores claros
6. ✅ Soporta los 3 formatos (base64, url, stream)

---

**Estado:** ⏳ **PENDIENTE DE IMPLEMENTACIÓN EN JUNTIFY**  
**Prioridad:** 🔴 ALTA - Requerido para funcionalidad de detalles de reunión  

**Última actualización:** 02/02/2026  
**Contacto Panel DDU:** Para cualquier duda sobre este endpoint
