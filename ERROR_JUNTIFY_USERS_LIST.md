# 🔴 ERROR CRÍTICO - Endpoint /api/users/list

## Problema Detectado

El endpoint `GET /api/users/list` está fallando con el siguiente error:

```
SQLSTATE[42S02]: Base table or view not found: 1146 
Table 'juntify_new.integrantes_empresa' doesn't exist

SQL: select `id`, `username`, `email` from `users` 
where (`username` like %jona% or `email` like %jona%) 
and `id` not in (select `iduser` from `integrantes_empresa` where `empresa_id` = 1) 
order by `username` asc
```

## Causa del Error

La consulta está intentando acceder a la tabla `integrantes_empresa` sin especificar la base de datos correcta. Laravel está asumiendo que está en la base de datos por defecto (`juntify_new`), pero la tabla realmente existe en `Juntify_Panels`.

## ✅ Solución

En el archivo `app/Http/Controllers/Api/UserManagementController.php`, línea 84-91, cambiar:

### ❌ CÓDIGO INCORRECTO (actual):
```php
$query->whereNotIn('id', function($q) use ($empresaId) {
    $q->select('iduser')
      ->from('juntify_panels.integrantes_empresa')  // ❌ No funciona
      ->where('empresa_id', $empresaId);
});
```

### ✅ CÓDIGO CORRECTO:
```php
$query->whereNotIn('id', function($q) use ($empresaId) {
    $q->select('iduser')
      ->from('Juntify_Panels.integrantes_empresa')  // ✅ Con mayúsculas
      ->where('empresa_id', $empresaId);
});
```

**IMPORTANTE:** El nombre de la base de datos es `Juntify_Panels` (con mayúscula en J y P), NO `juntify_panels`.

## Alternativa (Más Robusta)

Usar la conexión de base de datos configurada:

```php
use Illuminate\Support\Facades\DB;

// En el método listUsers():
if ($request->has('exclude_empresa_id') && $request->exclude_empresa_id) {
    $empresaId = $request->exclude_empresa_id;
    
    // Obtener IDs de usuarios que ya están en la empresa
    $existingUserIds = DB::connection('juntify_panels')
        ->table('integrantes_empresa')
        ->where('empresa_id', $empresaId)
        ->pluck('iduser')
        ->toArray();
    
    // Excluir esos usuarios
    if (!empty($existingUserIds)) {
        $query->whereNotIn('id', $existingUserIds);
    }
}
```

## Verificación

Después de aplicar el cambio, probar con:

```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/users/list?search=jona&exclude_empresa_id=1' -Method GET
```

Debería retornar:
```json
{
  "success": true,
  "users": [
    {
      "id": "uuid-here",
      "username": "jonathan",
      "email": "jonathan@example.com",
      "name": "Jonathan Usuario"
    }
  ],
  "total": 1
}
```

## Archivos Afectados

- `app/Http/Controllers/Api/UserManagementController.php` (líneas 84-91)

## Prioridad

🔴 **CRÍTICA** - Este error impide que Panel DDU pueda buscar y añadir usuarios.

---

**Fecha:** 02/02/2026  
**Reportado por:** Panel DDU  
**Estado:** PENDIENTE CORRECCIÓN
