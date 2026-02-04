# Endpoints Requeridos para Panel DDU - Juntify

## 📋 Resumen
Necesito implementar 3 endpoints en el proyecto Juntify (puerto 8000) para que Panel DDU pueda:
1. Obtener lista de usuarios disponibles
2. Registrar usuarios como integrantes de una empresa
3. Obtener detalles completos de reuniones con sus archivos y tareas

---

## 🔹 ENDPOINT 1: Obtener Lista de Usuarios

### Propósito
Obtener todos los usuarios disponibles en Juntify para poder añadirlos como integrantes de empresas en Panel DDU.

### Especificaciones

**Ruta:** `GET /api/users/list`

**Headers:**
```
Content-Type: application/json
```

**Query Parameters (Opcionales):**
- `search` (string): Filtrar por nombre, username o email
- `exclude_empresa_id` (int): Excluir usuarios que ya pertenecen a esta empresa

**Response Esperado (200 OK):**
```json
{
  "success": true,
  "users": [
    {
      "id": "5b324294-6847-4e85-b9f6-1687a9922f75",
      "username": "juan_perez",
      "email": "juan@example.com",
      "name": "Juan Pérez García"
    },
    {
      "id": "7c435395-7958-5f96-c7g7-2798b0a33g86",
      "username": "maria_lopez",
      "email": "maria@example.com",
      "name": "María López Hernández"
    }
  ],
  "total": 2
}
```

### Implementación Sugerida

**Controlador:** `app/Http/Controllers/Api/UserManagementController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    /**
     * Obtener lista de usuarios
     */
    public function listUsers(Request $request)
    {
        $query = User::select('id', 'username', 'email', 'name');

        // Filtro de búsqueda
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Excluir usuarios que ya pertenecen a una empresa
        if ($request->has('exclude_empresa_id') && $request->exclude_empresa_id) {
            $empresaId = $request->exclude_empresa_id;
            $query->whereNotIn('id', function($q) use ($empresaId) {
                $q->select('iduser')
                  ->from('Juntify_Panels.integrantes_empresa')  // ⚠️ IMPORTANTE: Usar nombre completo de BD
                  ->where('empresa_id', $empresaId);
            });
        }

        $users = $query->orderBy('username', 'asc')->get();

        return response()->json([
            'success' => true,
            'users' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'name' => $user->name ?? $user->username
                ];
            }),
            'total' => $users->count()
        ]);
    }
}
```

---

## 🔹 ENDPOINT 2: Registrar Usuario como Integrante de Empresa

### Propósito
Añadir un usuario existente de Juntify como integrante de una empresa específica en la tabla `juntify_panels.integrantes_empresa`.

### Especificaciones

**Ruta:** `POST /api/users/add-to-company`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_id": "5b324294-6847-4e85-b9f6-1687a9922f75",
  "empresa_id": 1,
  "rol": "miembro"
}
```

**Parámetros:**
- `user_id` (string, required): UUID del usuario en tabla `users`
- `empresa_id` (int, required): ID de la empresa en tabla `juntify_panels.empresa`
- `rol` (string, required): Rol del usuario (`admin`, `miembro`, `administrador`)

**Response Esperado (201 Created):**
```json
{
  "success": true,
  "message": "Usuario añadido a la empresa exitosamente.",
  "integrante": {
    "id": 5,
    "user_id": "5b324294-6847-4e85-b9f6-1687a9922f75",
    "empresa_id": 1,
    "rol": "miembro",
    "user": {
      "username": "juan_perez",
      "email": "juan@example.com",
      "name": "Juan Pérez García"
    },
    "empresa": {
      "id": 1,
      "nombre_empresa": "DDU"
    }
  }
}
```

**Response Error (409 Conflict):**
```json
{
  "success": false,
  "message": "El usuario ya es integrante de esta empresa."
}
```

**Response Error (404 Not Found):**
```json
{
  "success": false,
  "message": "Usuario o empresa no encontrados."
}
```

### Implementación Sugerida

Añadir al mismo `UserManagementController.php`:

```php
/**
 * Añadir usuario a una empresa
 */
public function addToCompany(Request $request)
{
    $request->validate([
        'user_id' => 'required|string',
        'empresa_id' => 'required|integer',
        'rol' => 'required|string|in:admin,miembro,administrador'
    ]);

    // Verificar que el usuario existe
    $user = User::find($request->user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Usuario no encontrado.'
        ], 404);
    }

    // Verificar que la empresa existe
    $empresa = DB::connection('juntify_panels')
        ->table('empresa')
        ->where('id', $request->empresa_id)
        ->first();

    if (!$empresa) {
        return response()->json([
            'success' => false,
            'message' => 'Empresa no encontrada.'
        ], 404);
    }

    // Verificar si ya es integrante
    $existingMember = DB::connection('juntify_panels')
        ->table('integrantes_empresa')
        ->where('iduser', $request->user_id)
        ->where('empresa_id', $request->empresa_id)
        ->first();

    if ($existingMember) {
        return response()->json([
            'success' => false,
            'message' => 'El usuario ya es integrante de esta empresa.'
        ], 409);
    }

    // Insertar nuevo integrante
    $integranteId = DB::connection('juntify_panels')
        ->table('integrantes_empresa')
        ->insertGetId([
            'iduser' => $request->user_id,
            'empresa_id' => $request->empresa_id,
            'rol' => $request->rol,
            'created_at' => now(),
            'updated_at' => now()
        ]);

    return response()->json([
        'success' => true,
        'message' => 'Usuario añadido a la empresa exitosamente.',
        'integrante' => [
            'id' => $integranteId,
            'user_id' => $request->user_id,
            'empresa_id' => $request->empresa_id,
            'rol' => $request->rol,
            'user' => [
                'username' => $user->username,
                'email' => $user->email,
                'name' => $user->name ?? $user->username
            ],
            'empresa' => [
                'id' => $empresa->id,
                'nombre_empresa' => $empresa->nombre_empresa
            ]
        ]
    ], 201);
}
```

---

## 🔹 ENDPOINT 3: Obtener Detalles de Reuniones

### Propósito
Obtener información completa de las reuniones de un usuario, incluyendo:
- Contenedor al que pertenece (si aplica)
- Archivo `.ju` (audio encriptado)
- Tareas asociadas
- Transcripciones

### Especificaciones

**Ruta:** `GET /api/meetings/{meeting_id}/details`

**Headers:**
```
Content-Type: application/json
```

**Path Parameters:**
- `meeting_id` (string, required): UUID de la reunión

**Query Parameters (Opcionales):**
- `user_id` (string): UUID del usuario para verificar permisos
- `include` (string): Valores separados por coma: `container,audio,tasks,transcription` (default: todos)

**Response Esperado (200 OK):**
```json
{
  "success": true,
  "meeting": {
    "id": "8d445506-8069-6g07-d8h8-3809c1b44h97",
    "meeting_name": "Reunión de Planificación Q1 2026",
    "meeting_date": "2026-02-02T10:00:00.000000Z",
    "duration_minutes": 45,
    "created_at": "2026-02-02T09:30:00.000000Z",
    "updated_at": "2026-02-02T11:00:00.000000Z"
  },
  "container": {
    "id": 12,
    "name": "Proyecto Alpha",
    "description": "Contenedor para reuniones del proyecto Alpha",
    "folder_id": "1A2B3C4D5E6F7G8H9I0J"
  },
  "audio_file": {
    "filename": "meeting_8d445506.ju",
    "file_path": "/storage/meetings/8d445506-8069-6g07-d8h8-3809c1b44h97/meeting_8d445506.ju",
    "file_size_bytes": 5242880,
    "file_size_mb": 5.0,
    "encrypted": true,
    "google_drive_file_id": "1Z2Y3X4W5V6U7T8S9R0Q",
    "download_url": "https://drive.google.com/file/d/1Z2Y3X4W5V6U7T8S9R0Q/view"
  },
  "transcription": {
    "id": 45,
    "transcription_text": "Texto completo de la transcripción...",
    "language": "es-MX",
    "confidence_score": 0.95,
    "created_at": "2026-02-02T10:50:00.000000Z"
  },
  "tasks": [
    {
      "id": 101,
      "task_description": "Revisar propuesta de presupuesto",
      "assigned_to_user_id": "5b324294-6847-4e85-b9f6-1687a9922f75",
      "assigned_to_username": "juan_perez",
      "status": "pending",
      "due_date": "2026-02-10",
      "priority": "high",
      "created_at": "2026-02-02T10:45:00.000000Z"
    },
    {
      "id": 102,
      "task_description": "Preparar presentación para stakeholders",
      "assigned_to_user_id": "7c435395-7958-5f96-c7g7-2798b0a33g86",
      "assigned_to_username": "maria_lopez",
      "status": "in_progress",
      "due_date": "2026-02-08",
      "priority": "medium",
      "created_at": "2026-02-02T10:47:00.000000Z"
    }
  ],
  "permissions": {
    "can_edit": true,
    "can_delete": true,
    "can_share": true,
    "is_owner": true
  }
}
```

**Response Error (404 Not Found):**
```json
{
  "success": false,
  "message": "Reunión no encontrada."
}
```

**Response Error (403 Forbidden):**
```json
{
  "success": false,
  "message": "No tienes permisos para acceder a esta reunión."
}
```

### Implementación Sugerida

**Controlador:** `app/Http/Controllers/Api/MeetingDetailsController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MeetingDetailsController extends Controller
{
    /**
     * Obtener detalles completos de una reunión
     */
    public function getDetails(Request $request, $meetingId)
    {
        // Buscar la reunión
        $meeting = DB::connection('juntify_panels')
            ->table('meetings')
            ->where('id', $meetingId)
            ->first();

        if (!$meeting) {
            return response()->json([
                'success' => false,
                'message' => 'Reunión no encontrada.'
            ], 404);
        }

        // Verificar permisos (opcional si se pasa user_id)
        $userId = $request->query('user_id');
        $canAccess = true;
        
        if ($userId) {
            // Verificar si el usuario tiene acceso a esta reunión
            $userMeeting = DB::connection('juntify_panels')
                ->table('user_meetings')
                ->where('meeting_id', $meetingId)
                ->where('user_id', $userId)
                ->first();
            
            $canAccess = $userMeeting !== null;
        }

        if (!$canAccess) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para acceder a esta reunión.'
            ], 403);
        }

        $response = [
            'success' => true,
            'meeting' => [
                'id' => $meeting->id,
                'meeting_name' => $meeting->meeting_name,
                'meeting_date' => $meeting->meeting_date,
                'duration_minutes' => $meeting->duration_minutes,
                'created_at' => $meeting->created_at,
                'updated_at' => $meeting->updated_at
            ]
        ];

        // Obtener contenedor (si existe)
        $container = DB::connection('juntify_panels')
            ->table('meeting_content_containers')
            ->where('id', $meeting->container_id)
            ->first();

        if ($container) {
            $response['container'] = [
                'id' => $container->id,
                'name' => $container->name,
                'description' => $container->description,
                'folder_id' => $container->folder_id
            ];
        } else {
            $response['container'] = null;
        }

        // Obtener archivo .ju (audio)
        $audioPath = "meetings/{$meetingId}/meeting_{$meetingId}.ju";
        $audioExists = Storage::exists($audioPath);
        
        if ($audioExists) {
            $fileSize = Storage::size($audioPath);
            $response['audio_file'] = [
                'filename' => "meeting_{$meetingId}.ju",
                'file_path' => Storage::path($audioPath),
                'file_size_bytes' => $fileSize,
                'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                'encrypted' => true,
                'google_drive_file_id' => $meeting->google_drive_file_id ?? null,
                'download_url' => $meeting->google_drive_file_id 
                    ? "https://drive.google.com/file/d/{$meeting->google_drive_file_id}/view"
                    : null
            ];
        } else {
            $response['audio_file'] = null;
        }

        // Obtener transcripción
        $transcription = DB::connection('juntify_panels')
            ->table('meeting_transcriptions')
            ->where('meeting_id', $meetingId)
            ->first();

        if ($transcription) {
            $response['transcription'] = [
                'id' => $transcription->id,
                'transcription_text' => $transcription->transcription_text,
                'language' => $transcription->language ?? 'es-MX',
                'confidence_score' => $transcription->confidence_score ?? null,
                'created_at' => $transcription->created_at
            ];
        } else {
            $response['transcription'] = null;
        }

        // Obtener tareas
        $tasks = DB::connection('juntify_panels')
            ->table('meeting_tasks as mt')
            ->leftJoin('juntify.users as u', 'mt.assigned_to_user_id', '=', 'u.id')
            ->where('mt.meeting_id', $meetingId)
            ->select(
                'mt.id',
                'mt.task_description',
                'mt.assigned_to_user_id',
                'u.username as assigned_to_username',
                'mt.status',
                'mt.due_date',
                'mt.priority',
                'mt.created_at'
            )
            ->orderBy('mt.created_at', 'desc')
            ->get();

        $response['tasks'] = $tasks->map(function($task) {
            return [
                'id' => $task->id,
                'task_description' => $task->task_description,
                'assigned_to_user_id' => $task->assigned_to_user_id,
                'assigned_to_username' => $task->assigned_to_username,
                'status' => $task->status ?? 'pending',
                'due_date' => $task->due_date,
                'priority' => $task->priority ?? 'medium',
                'created_at' => $task->created_at
            ];
        })->toArray();

        // Permisos del usuario (si user_id está presente)
        if ($userId) {
            $userMeeting = DB::connection('juntify_panels')
                ->table('user_meetings')
                ->where('meeting_id', $meetingId)
                ->where('user_id', $userId)
                ->first();

            $response['permissions'] = [
                'can_edit' => $userMeeting ? $userMeeting->can_edit ?? true : false,
                'can_delete' => $userMeeting ? $userMeeting->can_delete ?? false : false,
                'can_share' => $userMeeting ? $userMeeting->can_share ?? true : false,
                'is_owner' => $userMeeting ? $userMeeting->is_owner ?? false : false
            ];
        }

        return response()->json($response);
    }
}
```

---

## 📝 Configuración de Rutas

**Ubicación:** `routes/api.php`

Agregar al final del archivo:

```php
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\MeetingDetailsController;

// Gestión de Usuarios
Route::get('/users/list', [UserManagementController::class, 'listUsers']);
Route::post('/users/add-to-company', [UserManagementController::class, 'addToCompany']);

// Detalles de Reuniones
Route::get('/meetings/{meeting_id}/details', [MeetingDetailsController::class, 'getDetails']);
```

---

## 🧪 Pruebas

### Probar Endpoint 1: Lista de Usuarios
```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/users/list' -Method GET
```

Con filtros:
```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/users/list?search=juan&exclude_empresa_id=1' -Method GET
```

### Probar Endpoint 2: Añadir Usuario a Empresa
```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/users/add-to-company' `
  -Method POST `
  -ContentType 'application/json' `
  -Body '{"user_id":"5b324294-6847-4e85-b9f6-1687a9922f75","empresa_id":1,"rol":"miembro"}'
```

### Probar Endpoint 3: Detalles de Reunión
```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/meetings/8d445506-8069-6g07-d8h8-3809c1b44h97/details?user_id=5b324294-6847-4e85-b9f6-1687a9922f75' -Method GET
```

---

## 🔧 Comandos Post-Implementación

Después de crear los archivos, ejecutar:

```bash
php artisan route:clear
php artisan cache:clear
php artisan config:clear
php artisan route:list | grep -E "users|meetings"
```

---

## 📌 Resumen de Endpoints

| Método | Ruta | Propósito |
|--------|------|-----------|
| `GET` | `/api/users/list` | Obtener lista de usuarios disponibles |
| `POST` | `/api/users/add-to-company` | Registrar usuario como integrante de empresa |
| `GET` | `/api/meetings/{id}/details` | Obtener detalles completos de reunión |

---

## 🔐 Notas de Seguridad

1. **Validación de permisos:** Asegurar que solo usuarios autorizados accedan a reuniones
2. **Autenticación:** Considerar agregar middleware de autenticación API (Sanctum o similar)
3. **Rate limiting:** Implementar límite de peticiones para evitar abuso
4. **Validación de UUIDs:** Verificar formato de IDs antes de consultar BD

---

## 📋 Tablas de Base de Datos Involucradas

### Base de datos: `juntify`
- `users` - Usuarios del sistema

### Base de datos: `juntify_panels`
- `empresa` - Empresas registradas
- `integrantes_empresa` - Relación usuarios-empresas
- `meetings` - Reuniones
- `meeting_content_containers` - Contenedores de reuniones
- `meeting_transcriptions` - Transcripciones de audio
- `meeting_tasks` - Tareas asignadas en reuniones
- `user_meetings` - Relación usuarios-reuniones con permisos
