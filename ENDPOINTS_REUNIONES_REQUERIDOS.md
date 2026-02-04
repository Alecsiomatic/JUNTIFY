# 📋 ENDPOINTS - Gestión de Reuniones

## ✅ Estado: IMPLEMENTADO Y OPERATIVO

**Fecha implementación:** 02/02/2026  
**Solicitado por:** Panel DDU  
**Servidor Juntify:** http://127.0.0.1:8000  

---

## Problema Resuelto

El Panel DDU necesitaba obtener información de reuniones y grupos de reuniones para un usuario específico, pero estaba accediendo directamente a tablas de bases de datos que no existen en `juntify_panels`:

❌ **Errores anteriores:**
- `Table 'juntify_panels.meeting_group_user' doesn't exist`
- Acceso directo a `transcriptions_laravel` en la base de datos `juntify`

✅ **Solución implementada:** Endpoints en Juntify para centralizar el acceso a reuniones

---

## 1️⃣ Endpoint: Listar Reuniones del Usuario

### **GET** `/api/users/{user_id}/meetings`

**Descripción:** Obtener todas las reuniones (transcripciones) de un usuario específico

---

### Parámetros

#### Path Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `user_id` | string (UUID) | ✅ Sí | ID del usuario en Juntify |

#### Query Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `limit` | integer | ❌ No | Cantidad de reuniones a retornar. Default: `100` |
| `offset` | integer | ❌ No | Offset para paginación. Default: `0` |
| `order_by` | string | ❌ No | Campo para ordenar: `created_at`, `meeting_name`. Default: `created_at` |
| `order_dir` | string | ❌ No | Dirección: `asc` o `desc`. Default: `desc` |

---

### Respuesta Esperada

#### Success Response (200 OK)

```json
{
  "success": true,
  "user": {
    "id": "5b324294-6847-4e85-b9f6-1687a9922f75",
    "username": "Administrador_DDU",
    "email": "ddujuntify@gmail.com"
  },
  "meetings": [
    {
      "id": 123,
      "meeting_name": "Reunión de Planificación Q1 2026",
      "username": "Administrador_DDU",
      "transcript_drive_id": "1abc-2def-3ghi",
      "audio_drive_id": "4jkl-5mno-6pqr",
      "transcript_download_url": "https://drive.google.com/...",
      "audio_download_url": "https://drive.google.com/...",
      "status": "completed",
      "duration_minutes": 45,
      "created_at": "2026-02-01T10:30:00.000000Z",
      "updated_at": "2026-02-01T11:15:00.000000Z"
    },
    {
      "id": 122,
      "meeting_name": "Daily Standup",
      "username": "Administrador_DDU",
      "transcript_drive_id": "7stu-8vwx-9yz",
      "audio_drive_id": "0abc-1def-2ghi",
      "transcript_download_url": "https://drive.google.com/...",
      "audio_download_url": null,
      "status": "completed",
      "duration_minutes": 15,
      "created_at": "2026-01-31T09:00:00.000000Z",
      "updated_at": "2026-01-31T09:15:00.000000Z"
    }
  ],
  "pagination": {
    "total": 47,
    "limit": 100,
    "offset": 0,
    "has_more": false
  },
  "stats": {
    "total_meetings": 47,
    "this_week": 5,
    "this_month": 18,
    "total_duration_minutes": 2340
  }
}
```

#### Error Response (404 Not Found)

```json
{
  "success": false,
  "message": "Usuario no encontrado",
  "user_id": "invalid-uuid"
}
```

---

### Implementación Sugerida en Juntify

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class UserMeetingsController extends Controller
{
    /**
     * Obtener reuniones de un usuario
     */
    public function getUserMeetings(Request $request, string $userId): JsonResponse
    {
        try {
            // Verificar que el usuario existe
            $user = DB::connection('juntify')
                ->table('users')
                ->where('id', $userId)
                ->first(['id', 'username', 'email']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'user_id' => $userId
                ], 404);
            }

            // Parámetros de consulta
            $limit = $request->query('limit', 100);
            $offset = $request->query('offset', 0);
            $orderBy = $request->query('order_by', 'created_at');
            $orderDir = $request->query('order_dir', 'desc');

            // Obtener reuniones del usuario
            $query = DB::connection('juntify')
                ->table('transcriptions_laravel')
                ->where('username', $user->username);

            // Total de reuniones
            $total = $query->count();

            // Obtener reuniones con paginación
            $meetings = $query
                ->orderBy($orderBy, $orderDir)
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->map(function ($meeting) {
                    return [
                        'id' => $meeting->id,
                        'meeting_name' => $meeting->meeting_name,
                        'username' => $meeting->username,
                        'transcript_drive_id' => $meeting->transcript_drive_id,
                        'audio_drive_id' => $meeting->audio_drive_id,
                        'transcript_download_url' => $meeting->transcript_download_url ?? null,
                        'audio_download_url' => $meeting->audio_download_url ?? null,
                        'status' => 'completed', // Ajustar según lógica de Juntify
                        'duration_minutes' => null, // Agregar si existe en la tabla
                        'created_at' => $meeting->created_at,
                        'updated_at' => $meeting->updated_at,
                    ];
                });

            // Calcular estadísticas
            $now = Carbon::now();
            $thisWeekStart = $now->copy()->startOfWeek();
            $thisMonthStart = $now->copy()->startOfMonth();

            $allMeetings = DB::connection('juntify')
                ->table('transcriptions_laravel')
                ->where('username', $user->username)
                ->get();

            $stats = [
                'total_meetings' => $total,
                'this_week' => $allMeetings->filter(function ($m) use ($thisWeekStart) {
                    return Carbon::parse($m->created_at)->gte($thisWeekStart);
                })->count(),
                'this_month' => $allMeetings->filter(function ($m) use ($thisMonthStart) {
                    return Carbon::parse($m->created_at)->gte($thisMonthStart);
                })->count(),
                'total_duration_minutes' => 0, // Agregar si existe el campo
            ];

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ],
                'meetings' => $meetings,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total,
                ],
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reuniones del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

---

## 2️⃣ Endpoint: Listar Grupos de Reuniones del Usuario

### **GET** `/api/users/{user_id}/meeting-groups`

**Descripción:** Obtener todos los grupos de reuniones donde el usuario es dueño o miembro

---

### Parámetros

#### Path Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `user_id` | string (UUID) | ✅ Sí | ID del usuario en Juntify |

#### Query Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `include_members` | boolean | ❌ No | Incluir lista de miembros. Default: `true` |
| `include_meetings_count` | boolean | ❌ No | Incluir conteo de reuniones. Default: `true` |

---

### Respuesta Esperada

#### Success Response (200 OK)

```json
{
  "success": true,
  "user": {
    "id": "5b324294-6847-4e85-b9f6-1687a9922f75",
    "username": "Administrador_DDU"
  },
  "groups": [
    {
      "id": 1,
      "name": "Equipo de Desarrollo",
      "description": "Reuniones del equipo técnico",
      "owner_id": "5b324294-6847-4e85-b9f6-1687a9922f75",
      "is_owner": true,
      "members_count": 5,
      "meetings_count": 12,
      "members": [
        {
          "id": "5b324294-6847-4e85-b9f6-1687a9922f75",
          "username": "Administrador_DDU",
          "email": "ddujuntify@gmail.com",
          "added_at": "2026-01-15T10:00:00.000000Z"
        },
        {
          "id": "5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc",
          "username": "Jona0327",
          "email": "jona03278@gmail.com",
          "added_at": "2026-01-16T14:30:00.000000Z"
        }
      ],
      "created_at": "2026-01-15T10:00:00.000000Z",
      "updated_at": "2026-02-01T15:20:00.000000Z"
    },
    {
      "id": 2,
      "name": "Reuniones de Producto",
      "description": "Planificación y roadmap",
      "owner_id": "other-user-uuid",
      "is_owner": false,
      "members_count": 3,
      "meetings_count": 8,
      "members": [],
      "created_at": "2026-01-20T11:00:00.000000Z",
      "updated_at": "2026-01-30T16:45:00.000000Z"
    }
  ],
  "total": 2,
  "stats": {
    "total_groups": 2,
    "owned_groups": 1,
    "member_groups": 1
  }
}
```

---

### Implementación Sugerida en Juntify

```php
/**
 * Obtener grupos de reuniones del usuario
 */
public function getUserMeetingGroups(Request $request, string $userId): JsonResponse
{
    try {
        // Verificar que el usuario existe
        $user = DB::connection('juntify')
            ->table('users')
            ->where('id', $userId)
            ->first(['id', 'username', 'email']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
                'user_id' => $userId
            ], 404);
        }

        $includeMembers = $request->query('include_members', true);
        $includeMeetingsCount = $request->query('include_meetings_count', true);

        // Obtener grupos donde el usuario es dueño
        $ownedGroups = DB::connection('juntify')
            ->table('meeting_groups')
            ->where('owner_id', $userId)
            ->get();

        // Obtener grupos donde el usuario es miembro
        $memberGroups = DB::connection('juntify')
            ->table('meeting_group_user')
            ->join('meeting_groups', 'meeting_group_user.meeting_group_id', '=', 'meeting_groups.id')
            ->where('meeting_group_user.user_id', $userId)
            ->where('meeting_groups.owner_id', '!=', $userId) // Evitar duplicados
            ->select('meeting_groups.*')
            ->get();

        // Combinar grupos
        $allGroups = $ownedGroups->concat($memberGroups);

        $groups = $allGroups->map(function ($group) use ($user, $includeMembers, $includeMeetingsCount) {
            $groupData = [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'owner_id' => $group->owner_id,
                'is_owner' => $group->owner_id === $user->id,
                'created_at' => $group->created_at,
                'updated_at' => $group->updated_at,
            ];

            // Incluir conteo de miembros
            if ($includeMembers || $includeMeetingsCount) {
                $membersCount = DB::connection('juntify')
                    ->table('meeting_group_user')
                    ->where('meeting_group_id', $group->id)
                    ->count();
                $groupData['members_count'] = $membersCount + 1; // +1 por el owner
            }

            // Incluir lista de miembros
            if ($includeMembers) {
                $members = DB::connection('juntify')
                    ->table('meeting_group_user as mgu')
                    ->join('users as u', 'mgu.user_id', '=', 'u.id')
                    ->where('mgu.meeting_group_id', $group->id)
                    ->select('u.id', 'u.username', 'u.email', 'mgu.created_at as added_at')
                    ->get();

                // Agregar al owner a la lista
                $owner = DB::connection('juntify')
                    ->table('users')
                    ->where('id', $group->owner_id)
                    ->first(['id', 'username', 'email']);

                if ($owner) {
                    $members->prepend((object)[
                        'id' => $owner->id,
                        'username' => $owner->username,
                        'email' => $owner->email,
                        'added_at' => $group->created_at,
                    ]);
                }

                $groupData['members'] = $members;
            } else {
                $groupData['members'] = [];
            }

            // Incluir conteo de reuniones
            if ($includeMeetingsCount) {
                $meetingsCount = DB::connection('juntify')
                    ->table('meeting_group_meeting')
                    ->where('meeting_group_id', $group->id)
                    ->count();
                $groupData['meetings_count'] = $meetingsCount;
            }

            return $groupData;
        });

        $ownedCount = $groups->where('is_owner', true)->count();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
            'groups' => $groups->values(),
            'total' => $groups->count(),
            'stats' => [
                'total_groups' => $groups->count(),
                'owned_groups' => $ownedCount,
                'member_groups' => $groups->count() - $ownedCount,
            ],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener grupos del usuario',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

---

## 3️⃣ Endpoint: Obtener Detalles de una Reunión

### **GET** `/api/meetings/{meeting_id}`

**Descripción:** Obtener información detallada de una reunión específica

---

### Parámetros

#### Path Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `meeting_id` | integer | ✅ Sí | ID de la reunión |

---

### Respuesta Esperada

```json
{
  "success": true,
  "meeting": {
    "id": 123,
    "meeting_name": "Reunión de Planificación Q1 2026",
    "username": "Administrador_DDU",
    "user": {
      "id": "5b324294-6847-4e85-b9f6-1687a9922f75",
      "username": "Administrador_DDU",
      "email": "ddujuntify@gmail.com"
    },
    "transcript_drive_id": "1abc-2def-3ghi",
    "audio_drive_id": "4jkl-5mno-6pqr",
    "transcript_download_url": "https://drive.google.com/...",
    "audio_download_url": "https://drive.google.com/...",
    "status": "completed",
    "duration_minutes": 45,
    "transcript_content": "Contenido completo de la transcripción...",
    "created_at": "2026-02-01T10:30:00.000000Z",
    "updated_at": "2026-02-01T11:15:00.000000Z",
    "shared_with_groups": [
      {
        "group_id": 1,
        "group_name": "Equipo de Desarrollo",
        "shared_by": "5b324294-6847-4e85-b9f6-1687a9922f75",
        "shared_at": "2026-02-01T12:00:00.000000Z"
      }
    ]
  }
}
```

---

## 4️⃣ Endpoint: Descargar Archivos de Reunión

### **GET** `/api/meetings/{meeting_id}/download/{file_type}`

**Descripción:** Descargar archivo .ju (transcripción) o audio de una reunión. **Juntify maneja el token de Google Drive y descarga el archivo.**

⚠️ **IMPORTANTE:** Este endpoint es manejado completamente por Juntify para evitar exponer tokens de Google Drive al Panel DDU.

---

### Parámetros

#### Path Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `meeting_id` | integer | ✅ Sí | ID de la reunión |
| `file_type` | string | ✅ Sí | Tipo: `transcript` o `audio` |

#### Query Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `username` | string | ✅ Sí | Username del dueño de la reunión |
| `format` | string | ❌ No | Formato: `base64`, `url`, `stream`. Default: `base64` |

---

### Respuesta Esperada - Base64 (Default)

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

### Respuesta Esperada - URL

```json
{
  "success": true,
  "meeting_id": 5,
  "file_type": "audio",
  "file_name": "audio_reunion.mp3",
  "download_url": "https://drive.google.com/uc?export=download&id=...",
  "drive_id": "1ABC123..."
}
```

---

### Flujo de Seguridad

1. Panel DDU solicita archivo con `meeting_id` y `username`
2. Juntify busca Google Token del usuario en su BD
3. Juntify descarga archivo desde Google Drive
4. Juntify envía archivo al Panel DDU (nunca expone el token)

**Beneficios:**
- ✅ Panel DDU nunca maneja tokens de Google
- ✅ Seguridad centralizada en Juntify
- ✅ Control de permisos en un solo lugar

Ver documentación completa en: [ENDPOINT_DESCARGA_ARCHIVOS_REUNION.md](./ENDPOINT_DESCARGA_ARCHIVOS_REUNION.md)

---

## Registro de Rutas en Juntify

### `routes/api.php`

```php
use App\Http\Controllers\Api\UserMeetingsController;
use App\Http\Controllers\Api\MeetingDownloadController;

// Reuniones del usuario
Route::get('/users/{user_id}/meetings', [UserMeetingsController::class, 'getUserMeetings']);

// Grupos de reuniones del usuario
Route::get('/users/{user_id}/meeting-groups', [UserMeetingsController::class, 'getUserMeetingGroups']);

// Detalles de una reunión específica
Route::get('/meetings/{meeting_id}', [UserMeetingsController::class, 'getMeetingDetails']);

// Descargar archivos de reunión (transcripción o audio)
Route::get('/meetings/{meeting_id}/download/{file_type}', [MeetingDownloadController::class, 'downloadFile'])
    ->where('file_type', 'transcript|audio');
```

---

## Ejemplos de Uso

### 1. Obtener reuniones del usuario DDU

```bash
GET http://127.0.0.1:8000/api/users/5b324294-6847-4e85-b9f6-1687a9922f75/meetings
```

### 2. Obtener grupos del usuario

```bash
GET http://127.0.0.1:8000/api/users/5b324294-6847-4e85-b9f6-1687a9922f75/meeting-groups
```


### 4. Descargar transcripción (.ju)

```bash
GET http://127.0.0.1:8000/api/meetings/5/download/transcript?username=Jona0327
```

### 5. Descargar audio

```bash
GET http://127.0.0.1:8000/api/meetings/5/download/audio?username=Jona0327&format=stream
```
### 3. Obtener detalles de una reunión

```bash
GET http://127.0.0.1:8000/api/meetings/123
```


# Descargar transcripción en base64
$response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/meetings/5/download/transcript?username=Jona0327"
$bytes = [Convert]::FromBase64String($response.file_content)
[IO.File]::WriteAllBytes("C:\Downloads\reunion.ju", $bytes)

# Descargar audio como archivo directo
Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/meetings/5/download/audio?username=Jona0327&format=stream" `
    -OutFile "C:\Downloads\audio.mp3"
### Probar con PowerShell

```powershell
# Obtener reuniones
$userId = "5b324294-6847-4e85-b9f6-1687a9922f75"
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/users/$userId/meetings" -Method GET

# Obtener grupos
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/users/$userId/meeting-gde reuniones
2. ✅ `app/Http/Controllers/Api/MeetingDownloadController.php` - Controlador de descargas
3
# Obtener detalles de reunión
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/meetings/123" -Method GET
```

---

## Beneficios

✅ **Eliminación de dependencia directa a BD:** Panel DDU no necesita acceder directamente a las tablas de Juntify  
✅ **Centralización:** Toda la lógica de reuniones en Juntify  
✅ **Seguridad:** Control de acceso centralizado  
✅ **Escalabilidad:** Fácil agregar paginación, filtros, búsqueda  
✅ **Mantenibilidad:** Cambios en la estructura de BD solo afectan a Juntify  
✅ **Consistencia:** Misma estructura de datos para todos los clientes  

---

## Archivos a Crear en Juntify

1. ✅ `app/Http/Controllers/Api/UserMeetingsController.php` - Controlador nuevo
2. ✅ `routes/api.php` - Agregar rutas
⏳ `GET /api/meetings/{id}/download/transcript` descarga archivo .ju
5. ⏳ `GET /api/meetings/{id}/download/audio` descarga archivo de audio
6. ✅ Las estadísticas son correctas
7. ✅ La paginación funciona correctamente
8. ✅ Maneja errores (usuario no encontrado, etc.)
9. ⏳ Juntify maneja tokens de Google Drive de forma segura
## Verificación

Después de implementar, verificar que:

1. ✅ `GET /api/users/{user_id}/meetings` retorna reuniones del usuario
2. ✅ `GET /api/users/{user_id}/meeting-groups` retorna grupos donde participa
3. ✅ `GET /api/meetings/{meeting_id}` retorna detalles completos
4. ✅ Las estadísticas son correctas
5. ✅ La paginación funciona correctamente
6. ✅ Maneja errores (usuario no encontrado, etc.)

---

## 🧪 Testing en Producción

### Obtener reuniones del usuario
```powershell
$userId = "5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc"
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/users/$userId/meetings"
```

**Resultado verificado:** ✅ FUNCIONANDO
```json
{
  "success": true,
  "meetings": [
    {
      "id": 5,
      "meeting_name": "Reunión del 02/02/2026 12:13",
      "username": "Jona0327",
      "status": "completed"
    }
  ],
  "stats": {
    "total_meetings": 1,
    "this_week": 1,
    "this_month": 1
  }
}
```

### Obtener grupos del usuario
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/users/$userId/meeting-groups"
```

**Resultado verificado:** ✅ FUNCIONANDO

### Obtener detalles de reunión
```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/meetings/5'
| `GET /api/meetings/{id}/download/transcript` | ⏳ Pendiente | ❌ No probado |
| `GET /api/meetings/{id}/download/audio` | ⏳ Pendiente | ❌ No probado |
```

**Resultado verificado:** ✅ FUNCIONANDO

---

## 📊 Estado de Implementación

| Endpoint | Estado | Verificado |
|----------|--------|------------|
| `GET /api/users/{user_id}/meetings` | ✅ Implementado | ✅ Probado |
| `GET /api/users/{user_id}/meeting-groups` | ✅ Implementado | ✅ Probado |
| `GET /api/meetings/{meeting_id}` | ✅ Implementado | ✅ Probado |

---

**Estado:** ✅ **IMPLEMENTADO Y OPERATIVO EN JUNTIFY**  
**Panel DDU:** ✅ Integración completada  
**Última actualización:** 02/02/2026  
**Servidor:** http://127.0.0.1:8000
