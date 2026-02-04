# 📋 ENDPOINT REQUERIDO - Listar Miembros de Empresa

## Solicitud a Juntify

**Fecha:** 02/02/2026  
**Solicitado por:** Panel DDU  
**Prioridad:** 🔴 ALTA

---

## Necesidad

El Panel DDU necesita obtener la lista completa de miembros (integrantes) de una empresa específica para mostrarlos en la interfaz de administración.

Actualmente, el Panel DDU no puede obtener esta información porque:
1. Las tablas están en diferentes bases de datos (`juntify_new` y `juntify_panels`)
2. No existe un endpoint centralizado para consultar miembros de una empresa
3. Las consultas directas a la base de datos no son la mejor práctica

---

## Endpoint Solicitado

### **GET** `/api/companies/{empresa_id}/members`

**Descripción:** Obtener lista completa de miembros/integrantes de una empresa específica

---

## Parámetros

### Path Parameters

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `empresa_id` | integer | ✅ Sí | ID de la empresa (ej: 3 para DDU) |

### Query Parameters (Opcionales)

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `include_owner` | boolean | ❌ No | Si es `true`, incluye al usuario principal/dueño de la empresa. Default: `true` |
| `status` | string | ❌ No | Filtrar por estado: `active`, `inactive`, `all`. Default: `all` |

---

## Respuesta Esperada

### Success Response (200 OK)

```json
{
  "success": true,
  "empresa": {
    "id": 1,
    "nombre": "DDU",
    "usuario_principal": "5b324294-6847-4e85-b9f6-1687a9922f75"
  },
  "members": [
    {
      "id": "5b324294-6847-4e85-b9f6-1687a9922f75",
      "username": "Administrador_DDU",
      "email": "ddujuntify@gmail.com",
      "name": "Administrador_DDU",
      "is_owner": true,
      "rol": null,
      "fecha_agregado": "2026-02-02T14:23:00.000000Z"
    },
    {
      "id": "5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc",
      "username": "Jona0327",
      "email": "jona03278@gmail.com",
      "name": "Jona0327",
      "is_owner": false,
      "rol": "admin",
      "fecha_agregado": "2026-02-02T14:23:15.000000Z"
    }
  ],
  "total": 2,
  "stats": {
    "total_members": 2,
    "admins": 1,
    "members": 1,
    "active": 2,
    "inactive": 0
  }
}
```

### Error Response (404 Not Found)

```json
{
  "success": false,
  "message": "Empresa no encontrada",
  "empresa_id": 999
}
```

### Error Response (500 Internal Server Error)

```json
{
  "success": false,
  "message": "Error al obtener miembros de la empresa",
  "error": "Descripción del error"
}
```

---

## Implementación Sugerida

### En Juntify: `app/Http/Controllers/Api/CompanyMembersController.php`

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
     * Obtener lista de miembros de una empresa
     */
    public function getMembers(Request $request, int $empresaId): JsonResponse
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

            $includeOwner = $request->query('include_owner', true);
            $members = [];

            // 1. Obtener el dueño/usuario principal si se solicita
            if ($includeOwner && $empresa->usuario_principal) {
                $owner = DB::connection('juntify')
                    ->table('users')
                    ->where('id', $empresa->usuario_principal)
                    ->first(['id', 'email', 'name']);

                if ($owner) {
                    $members[] = [
                        'id' => $owner->id,
                        'email' => $owner->email,
                        'name' => $owner->name ?? 'Usuario',
                        'is_owner' => true,
                        'rol' => null,
                        'fecha_agregado' => $empresa->created_at
                    ];
                }
            }

            // 2. Obtener integrantes de la tabla integrantes_empresa
            $integrantes = DB::connection('juntify_panels')
                ->table('integrantes_empresa as ie')
                ->join('juntify.users as u', 'ie.iduser', '=', 'u.id')
                ->where('ie.empresa_id', $empresaId)
                ->select(
                    'u.id',
                    'u.email',
                    'u.name',
                    'ie.rol',
                    'ie.fecha_agregado'
                )
                ->get();

            foreach ($integrantes as $integrante) {
                $members[] = [
                    'id' => $integrante->id,
                    'email' => $integrante->email,
                    'name' => $integrante->name ?? 'Usuario',
                    'is_owner' => false,
                    'rol' => $integrante->rol,
                    'fecha_agregado' => $integrante->fecha_agregado
                ];
            }

            // 3. Calcular estadísticas
            $totalMembers = count($members);
            $admins = count(array_filter($members, fn($m) => 
                $m['is_owner'] || in_array($m['rol'], ['admin', 'administrador'])
            ));

            return response()->json([
                'success' => true,
                'empresa' => [
                    'id' => $empresa->id,
                    'nombre' => $empresa->nombre,
                    'usuario_principal' => $empresa->usuario_principal
                ],
                'members' => $members,
                'total' => $totalMembers,
                'stats' => [
                    'total_members' => $totalMembers,
                    'admins' => $admins,
                    'members' => $totalMembers - $admins,
                    'active' => $totalMembers, // TODO: implementar lógica de activos
                    'inactive' => 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener miembros de la empresa',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

### Registro de Ruta en Juntify: `routes/api.php`

```php
use App\Http\Controllers\Api\CompanyMembersController;

// Obtener miembros de una empresa
Route::get('/companies/{empresa_id}/members', [CompanyMembersController::class, 'getMembers']);
```

---

## Ejemplos de Uso

### Obtener todos los miembros de DDU (empresa_id = 3)

```bash
GET http://127.0.0.1:8000/api/companies/3/members
```

**Respuesta:**
```json
{
  "success": true,
  "empresa": {
    "id": 1,
    "nombre": "DDU",
    "usuario_principal": "5b324294-6847-4e85-b9f6-1687a9922f75"
  },
  "members": [
    {
      "id": "5b324294-6847-4e85-b9f6-1687a9922f75",
      "email": "ddujuntify@gmail.com",
      "name": "Administrador_DDU",
      "is_owner": true,
      "rol": null,
      "fecha_agregado": "2026-02-02T14:23:00.000000Z"
    },
    {
      "id": "5b2161d8-eae9-4fdc-8ab6-992fa7a4bbdc",
      "email": "jona03278@gmail.com",
      "name": "Jona0327",
      "is_owner": false,
      "rol": "admin",
      "fecha_agregado": "2026-02-02T14:23:15.000000Z"
    }
  ],
  "total": 2
}
```

### Obtener miembros sin incluir al dueño

```bash
GET http://127.0.0.1:8000/api/companies/1/members?include_owner=false
```

### Probar con PowerShell

```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/companies/3/members' -Method GET
```

---

## Beneficios

✅ **Centralización:** Un solo punto de acceso para obtener miembros  
✅ **Consistencia:** Misma estructura de datos siempre  
✅ **Seguridad:** No expone acceso directo a la base de datos  
✅ **Mantenibilidad:** Más fácil modificar la lógica en un solo lugar  
✅ **Escalabilidad:** Puede agregar filtros, paginación, etc.  
✅ **Estadísticas:** Incluye información agregada útil  

---

## Archivos a Crear en Juntify

1. ✅ `app/Http/Controllers/Api/CompanyMembersController.php` - Controlador nuevo
2. ✅ `routes/api.php` - Agregar ruta al archivo existente

---

## Verificación

Después de implementar, verificar que:

1. ✅ El endpoint responde correctamente: `GET /api/companies/3/members`
2. ✅ Incluye al usuario principal (dueño) de la empresa
3. ✅ Incluye todos los integrantes de `integrantes_empresa`
4. ✅ El campo `is_owner` identifica al dueño
5. ✅ Las estadísticas son correctas
6. ✅ Maneja errores (empresa no encontrada, etc.)

---

**Estado:** ✅ **IMPLEMENTADO EN JUNTIFY**  
**Nota:** El Panel DDU ya está configurado para usar este endpoint. Ver documentación en archivo raíz del proyecto.

---

## Uso en Panel DDU

El Panel DDU ya tiene el código actualizado para usar este endpoint:

### Servicio: `app/Services/JuntifyApiService.php`
```php
public function getCompanyMembers(int $empresaId, bool $includeOwner = true): array
{
    $params = ['include_owner' => $includeOwner ? 'true' : 'false'];
    $response = Http::timeout(10)->get("{$this->baseUrl}/companies/{$empresaId}/members", $params);
    // ... manejo de respuesta
}
```

### Controlador: `app/Http/Controllers/MembersManagementController.php`
```php
public function index(Request $request)
{
    // Obtener miembros desde Juntify
    $membersResult = $this->juntifyApi->getCompanyMembers(3); // 3 = DDU
    
    if ($membersResult['success']) {
        $members = $membersResult['data']['members'];
        $stats = $membersResult['data']['stats'];
    }
    // ...
}
```

**Última actualización:** 02/02/2026  
**Estado Panel DDU:** ✅ Listo para usar el endpoint  
**Contacto Panel DDU:** Para cualquier duda sobre este endpoint
