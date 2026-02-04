# 📋 ENDPOINTS ADICIONALES REQUERIDOS - Gestión de Miembros

## Solicitud a Juntify

**Fecha:** 02/02/2026  
**Solicitado por:** Panel DDU  
**Prioridad:** 🔴 ALTA

---

## Necesidades

El Panel DDU necesita 3 endpoints adicionales para completar la funcionalidad de gestión de miembros:

1. **Cambiar rol de un miembro** - Para actualizar el rol de un integrante existente
2. **Eliminar miembro de la empresa** - Para remover un usuario de la empresa
3. **Obtener mis contactos de Juntify** - Para mostrar contactos del usuario antes de buscar en todos los usuarios

---

## 1️⃣ ENDPOINT: Cambiar Rol de Miembro

### **PATCH** `/api/companies/{empresa_id}/members/{user_id}/role`

**Descripción:** Actualizar el rol de un miembro específico en una empresa

### Parámetros

#### Path Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `empresa_id` | integer | ✅ Sí | ID de la empresa (ej: 3 para DDU) |
| `user_id` | string | ✅ Sí | UUID del usuario a actualizar |

#### Request Body

```json
{
  "rol": "admin"
}
```

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `rol` | string | ✅ Sí | Nuevo rol del usuario: `admin`, `miembro`, `colaborador`, etc. |

### Respuesta Esperada

#### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Rol actualizado correctamente",
  "member": {
    "id": "5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc",
    "name": "Jona0327",
    "email": "jona03278@gmail.com",
    "rol": "admin",
    "empresa_id": 3,
    "fecha_actualizacion": "2026-02-02T18:30:00.000000Z"
  }
}
```

#### Error Responses

```json
// 404 - Usuario no encontrado en la empresa
{
  "success": false,
  "message": "Usuario no es miembro de esta empresa",
  "user_id": "xxx",
  "empresa_id": 3
}

// 403 - No se puede cambiar rol del dueño
{
  "success": false,
  "message": "No se puede cambiar el rol del dueño de la empresa"
}

// 400 - Rol inválido
{
  "success": false,
  "message": "Rol inválido",
  "valid_roles": ["admin", "miembro", "colaborador"]
}
```

### Implementación Sugerida

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class CompanyMembersController extends Controller
{
    /**
     * Actualizar rol de un miembro
     */
    public function updateRole(Request $request, int $empresaId, string $userId): JsonResponse
    {
        try {
            // Validar el rol
            $validRoles = ['admin', 'miembro', 'colaborador', 'administrador'];
            $newRole = $request->input('rol');
            
            if (!in_array($newRole, $validRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol inválido',
                    'valid_roles' => $validRoles
                ], 400);
            }

            // Verificar que la empresa existe
            $empresa = DB::connection('juntify_panels')
                ->table('empresa')
                ->where('id', $empresaId)
                ->first();

            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa no encontrada',
                    'empresa_id' => $empresaId
                ], 404);
            }

            // Verificar que no es el dueño
            if ($empresa->usuario_principal === $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cambiar el rol del dueño de la empresa'
                ], 403);
            }

            // Buscar el integrante
            $integrante = DB::connection('juntify_panels')
                ->table('integrantes_empresa')
                ->where('empresa_id', $empresaId)
                ->where('iduser', $userId)
                ->first();

            if (!$integrante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es miembro de esta empresa',
                    'user_id' => $userId,
                    'empresa_id' => $empresaId
                ], 404);
            }

            // Actualizar el rol
            DB::connection('juntify_panels')
                ->table('integrantes_empresa')
                ->where('empresa_id', $empresaId)
                ->where('iduser', $userId)
                ->update([
                    'rol' => $newRole,
                    'updated_at' => now()
                ]);

            // Obtener información del usuario
            $user = DB::connection('juntify')
                ->table('users')
                ->where('id', $userId)
                ->first(['id', 'name', 'email']);

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado correctamente',
                'member' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'rol' => $newRole,
                    'empresa_id' => $empresaId,
                    'fecha_actualizacion' => now()->toIso8601String()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar rol',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

---

## 2️⃣ ENDPOINT: Eliminar Miembro de Empresa

### **DELETE** `/api/companies/{empresa_id}/members/{user_id}`

**Descripción:** Eliminar un miembro de una empresa

### Parámetros

#### Path Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `empresa_id` | integer | ✅ Sí | ID de la empresa (ej: 3 para DDU) |
| `user_id` | string | ✅ Sí | UUID del usuario a eliminar |

### Respuesta Esperada

#### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Miembro eliminado correctamente",
  "deleted_member": {
    "id": "5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc",
    "name": "Jona0327",
    "email": "jona03278@gmail.com",
    "empresa_id": 3
  }
}
```

#### Error Responses

```json
// 404 - Usuario no encontrado en la empresa
{
  "success": false,
  "message": "Usuario no es miembro de esta empresa",
  "user_id": "xxx",
  "empresa_id": 3
}

// 403 - No se puede eliminar al dueño
{
  "success": false,
  "message": "No se puede eliminar al dueño de la empresa"
}
```

### Implementación Sugerida

```php
/**
 * Eliminar un miembro de la empresa
 */
public function removeMember(int $empresaId, string $userId): JsonResponse
{
    try {
        // Verificar que la empresa existe
        $empresa = DB::connection('juntify_panels')
            ->table('empresa')
            ->where('id', $empresaId)
            ->first();

        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada',
                'empresa_id' => $empresaId
            ], 404);
        }

        // Verificar que no es el dueño
        if ($empresa->usuario_principal === $userId) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar al dueño de la empresa'
            ], 403);
        }

        // Buscar el integrante
        $integrante = DB::connection('juntify_panels')
            ->table('integrantes_empresa')
            ->where('empresa_id', $empresaId)
            ->where('iduser', $userId)
            ->first();

        if (!$integrante) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no es miembro de esta empresa',
                'user_id' => $userId,
                'empresa_id' => $empresaId
            ], 404);
        }

        // Obtener información del usuario antes de eliminar
        $user = DB::connection('juntify')
            ->table('users')
            ->where('id', $userId)
            ->first(['id', 'name', 'email']);

        // Eliminar el integrante
        DB::connection('juntify_panels')
            ->table('integrantes_empresa')
            ->where('empresa_id', $empresaId)
            ->where('iduser', $userId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Miembro eliminado correctamente',
            'deleted_member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'empresa_id' => $empresaId
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar miembro',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

---

## 3️⃣ ENDPOINT: Obtener Mis Contactos de Juntify

### **GET** `/api/users/{user_id}/contacts`

**Descripción:** Obtener lista de contactos de Juntify de un usuario específico para mostrarlos antes de buscar en todos los usuarios

### Parámetros

#### Path Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `user_id` | string | ✅ Sí | UUID del usuario autenticado (ej: usuario principal de DDU) |

#### Query Parameters (Opcionales)

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `exclude_empresa_id` | integer | ❌ No | Excluir usuarios que ya son miembros de esta empresa |
| `limit` | integer | ❌ No | Límite de resultados. Default: `50` |

### Respuesta Esperada

#### Success Response (200 OK)

```json
{
  "success": true,
  "user_id": "5b324294-6847-4e85-b9f6-1687a9922f75",
  "contacts": [
    {
      "id": "abc-123-def",
      "name": "María García",
      "email": "maria@example.com",
      "is_contact_since": "2026-01-15T10:30:00.000000Z",
      "is_added_to_empresa": false
    },
    {
      "id": "5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc",
      "name": "Jona0327",
      "email": "jona03278@gmail.com",
      "is_contact_since": "2026-01-10T08:00:00.000000Z",
      "is_added_to_empresa": true
    }
  ],
  "total": 2,
  "available_to_add": 1
}
```

#### Error Response (404 Not Found)

```json
{
  "success": false,
  "message": "Usuario no encontrado",
  "user_id": "xxx"
}
```

### Implementación Sugerida

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class UserContactsController extends Controller
{
    /**
     * Obtener contactos de un usuario
     */
    public function getContacts(Request $request, string $userId): JsonResponse
    {
        try {
            // Verificar que el usuario existe
            $user = DB::connection('juntify')
                ->table('users')
                ->where('id', $userId)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'user_id' => $userId
                ], 404);
            }

            $excludeEmpresaId = $request->query('exclude_empresa_id');
            $limit = $request->query('limit', 50);

            // Obtener contactos del usuario
            // NOTA: Ajustar según la estructura real de la tabla de contactos en Juntify
            // Asumiendo que existe una tabla 'user_contacts' o 'contacts'
            $contactsQuery = DB::connection('juntify')
                ->table('user_contacts as uc')
                ->join('users as u', 'uc.contact_user_id', '=', 'u.id')
                ->where('uc.user_id', $userId)
                ->select(
                    'u.id',
                    'u.name',
                    'u.email',
                    'uc.created_at as is_contact_since'
                )
                ->limit($limit);

            $contacts = $contactsQuery->get();

            // Si se especifica exclude_empresa_id, marcar cuáles ya están agregados
            $contactsArray = [];
            $availableToAdd = 0;

            foreach ($contacts as $contact) {
                $isAdded = false;

                if ($excludeEmpresaId) {
                    // Verificar si el contacto ya es miembro de la empresa
                    $empresa = DB::connection('juntify_panels')
                        ->table('empresa')
                        ->where('id', $excludeEmpresaId)
                        ->first();

                    if ($empresa) {
                        // Verificar si es el dueño
                        if ($empresa->usuario_principal === $contact->id) {
                            $isAdded = true;
                        } else {
                            // Verificar si está en integrantes_empresa
                            $isMember = DB::connection('juntify_panels')
                                ->table('integrantes_empresa')
                                ->where('empresa_id', $excludeEmpresaId)
                                ->where('iduser', $contact->id)
                                ->exists();
                            
                            $isAdded = $isMember;
                        }
                    }
                }

                if (!$isAdded) {
                    $availableToAdd++;
                }

                $contactsArray[] = [
                    'id' => $contact->id,
                    'name' => $contact->name ?? 'Usuario',
                    'email' => $contact->email,
                    'is_contact_since' => $contact->is_contact_since,
                    'is_added_to_empresa' => $isAdded
                ];
            }

            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'contacts' => $contactsArray,
                'total' => count($contactsArray),
                'available_to_add' => $availableToAdd
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener contactos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

---

## Registro de Rutas en Juntify

### En `routes/api.php`

```php
use App\Http\Controllers\Api\CompanyMembersController;
use App\Http\Controllers\Api\UserContactsController;

// Gestión de miembros de empresa
Route::patch('/companies/{empresa_id}/members/{user_id}/role', [CompanyMembersController::class, 'updateRole']);
Route::delete('/companies/{empresa_id}/members/{user_id}', [CompanyMembersController::class, 'removeMember']);

// Contactos de usuario
Route::get('/users/{user_id}/contacts', [UserContactsController::class, 'getContacts']);
```

---

## Ejemplos de Uso

### 1. Cambiar Rol de Miembro

```bash
PATCH http://127.0.0.1:8000/api/companies/3/members/5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc/role
Content-Type: application/json

{
  "rol": "admin"
}
```

**PowerShell:**
```powershell
$body = @{ rol = "admin" } | ConvertTo-Json
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/companies/3/members/5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc/role" -Method PATCH -Body $body -ContentType "application/json"
```

### 2. Eliminar Miembro

```bash
DELETE http://127.0.0.1:8000/api/companies/3/members/5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc
```

**PowerShell:**
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/companies/3/members/5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc" -Method DELETE
```

### 3. Obtener Mis Contactos

```bash
GET http://127.0.0.1:8000/api/users/5b324294-6847-4e85-b9f6-1687a9922f75/contacts?exclude_empresa_id=3
```

**PowerShell:**
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/users/5b324294-6847-4e85-b9f6-1687a9922f75/contacts?exclude_empresa_id=3" -Method GET
```

---

## Beneficios

✅ **Gestión Completa:** Permite administrar completamente los miembros (agregar, actualizar rol, eliminar)  
✅ **Experiencia Mejorada:** Los contactos aparecen primero, facilitando agregar usuarios conocidos  
✅ **Validaciones:** Protege al dueño de ser modificado o eliminado  
✅ **Consistencia:** Mantiene la estructura de respuesta uniforme  
✅ **Escalabilidad:** Permite agregar más funcionalidades en el futuro  

---

## Archivos a Crear/Modificar en Juntify

1. ✅ `app/Http/Controllers/Api/CompanyMembersController.php` - Agregar métodos `updateRole()` y `removeMember()`
2. ✅ `app/Http/Controllers/Api/UserContactsController.php` - Nuevo controlador para contactos
3. ✅ `routes/api.php` - Agregar 3 nuevas rutas

---

## Verificación

Después de implementar, verificar que:

### Cambiar Rol
1. ✅ Actualiza correctamente el rol en `integrantes_empresa`
2. ✅ No permite cambiar el rol del dueño
3. ✅ Valida roles permitidos
4. ✅ Retorna error si el usuario no es miembro

### Eliminar Miembro
1. ✅ Elimina correctamente el registro de `integrantes_empresa`
2. ✅ No permite eliminar al dueño
3. ✅ Retorna error si el usuario no es miembro

### Obtener Contactos
1. ✅ Retorna lista de contactos del usuario
2. ✅ Marca correctamente cuáles ya están agregados a la empresa
3. ✅ Respeta el límite especificado
4. ✅ Funciona con y sin `exclude_empresa_id`

---

## Nota Importante sobre Contactos

⚠️ **IMPORTANTE:** La implementación del endpoint de contactos asume que existe una tabla de contactos en Juntify (como `user_contacts`). 

**Si no existe esta tabla**, Juntify debe:
1. Confirmar cómo se almacenan los contactos en su sistema
2. Ajustar la consulta SQL según su estructura de datos
3. O crear una tabla de contactos si aún no existe:

```sql
CREATE TABLE user_contacts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    contact_user_id CHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_contact (user_id, contact_user_id)
);
```

---

**Estado:** ⏳ **PENDIENTE DE IMPLEMENTACIÓN EN JUNTIFY**  
**Última actualización:** 02/02/2026  
**Contacto Panel DDU:** Para cualquier duda sobre estos endpoints
