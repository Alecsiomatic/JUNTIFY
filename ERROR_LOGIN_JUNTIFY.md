# Error de Autenticación - Panel DDU

## 🔴 Problema Actual

El sistema Panel DDU intenta autenticar usuarios contra Juntify mediante el endpoint:
```
POST http://127.0.0.1:8000/api/auth/validate-user
```

**Error observado:**
- El usuario ingresa credenciales correctas
- El sistema muestra: "No tienes acceso a este sistema. Solo usuarios de DDU pueden ingresar."
- En los logs aparece: `belongs_to_company: false`
- Error HTTP 422 (Unprocessable Content) al intentar llamar al endpoint

## ❌ Causa del Error

El endpoint `/api/auth/validate-user` **NO EXISTE** en el servidor de Juntify (puerto 8000).

## 📋 Información del Sistema

### Usuario de Prueba:
- Email: `ddujuntify@gmail.com`
- Usuario existe en tabla `users` de base de datos `juntify`
- Usuario está registrado en tabla `integrantes_empresa` con empresa DDU (id: 1)

### Estructura de Base de Datos:
```
Base de datos: juntify
Tabla: users
- id: 5b324294-6847-4e85-b9f6-1687a9922f75
- username: Administrador_DDU
- email: ddujuntify@gmail.com

Base de datos: juntify_panels
Tabla: empresa
- id: 1
- nombre_empresa: DDU

Tabla: integrantes_empresa
- id: 2
- iduser: 5b324294-6847-4e85-b9f6-1687a9922f75
- empresa_id: 1
- rol: admin
```

## ✅ Solución Requerida

Necesito que en el proyecto **Juntify (puerto 8000)** se cree el siguiente endpoint de validación:

### 1. Crear Controlador en Juntify

**Ubicación:** `app/Http/Controllers/Api/AuthValidationController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthValidationController extends Controller
{
    public function validateUser(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'nombre_empresa' => 'required|string',
        ]);

        // Buscar usuario por email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'belongs_to_company' => false,
                'message' => 'Usuario no encontrado.'
            ], 401);
        }

        // Verificar contraseña
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'belongs_to_company' => false,
                'message' => 'Contraseña incorrecta.'
            ], 401);
        }

        // Verificar pertenencia a empresa
        $empresa = DB::connection('juntify_panels')
            ->table('integrantes_empresa')
            ->join('empresa', 'integrantes_empresa.empresa_id', '=', 'empresa.id')
            ->where('integrantes_empresa.iduser', $user->id)
            ->where('empresa.nombre_empresa', $request->nombre_empresa)
            ->select(
                'empresa.nombre_empresa',
                'integrantes_empresa.rol',
                'empresa.id as empresa_id'
            )
            ->first();

        if (!$empresa) {
            return response()->json([
                'success' => false,
                'belongs_to_company' => false,
                'message' => 'El usuario no pertenece a la empresa ' . $request->nombre_empresa . '.'
            ], 403);
        }

        // Usuario válido y pertenece a la empresa
        return response()->json([
            'success' => true,
            'belongs_to_company' => true,
            'message' => 'Autenticación exitosa.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name ?? $user->username,
                'email' => $user->email,
                'username' => $user->username,
            ],
            'company' => [
                'id' => $empresa->empresa_id,
                'nombre' => $empresa->nombre_empresa,
                'rol_usuario' => $empresa->rol
            ]
        ]);
    }
}
```

### 2. Agregar Ruta en Juntify

**Ubicación:** `routes/api.php`

Agregar al final del archivo:

```php
use App\Http\Controllers\Api\AuthValidationController;

Route::post('/auth/validate-user', [AuthValidationController::class, 'validateUser']);
```

### 3. Verificar Configuración de Base de Datos

En `config/database.php` de Juntify, asegurarse que exista la conexión:

```php
'juntify_panels' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => 'Juntify_Panels',
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

## 📝 Request Esperado

```json
POST http://127.0.0.1:8000/api/auth/validate-user
Content-Type: application/json

{
  "email": "ddujuntify@gmail.com",
  "password": "contraseña_del_usuario",
  "nombre_empresa": "DDU"
}
```

## ✅ Response Esperado (Éxito)

```json
{
  "success": true,
  "belongs_to_company": true,
  "message": "Autenticación exitosa.",
  "user": {
    "id": "5b324294-6847-4e85-b9f6-1687a9922f75",
    "name": "Administrador_DDU",
    "email": "ddujuntify@gmail.com",
    "username": "Administrador_DDU"
  },
  "company": {
    "id": 1,
    "nombre": "DDU",
    "rol_usuario": "admin"
  }
}
```

## ❌ Response Esperado (Error)

```json
{
  "success": false,
  "belongs_to_company": false,
  "message": "El usuario no pertenece a la empresa DDU."
}
```

## 🔧 Comandos Post-Implementación

Después de crear los archivos en Juntify, ejecutar:

```bash
php artisan route:clear
php artisan cache:clear
php artisan config:clear
```

## 🧪 Cómo Probar

Desde PowerShell o Terminal:

```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/auth/validate-user' `
  -Method POST `
  -ContentType 'application/json' `
  -Body '{"email":"ddujuntify@gmail.com","password":"tu_contraseña","nombre_empresa":"DDU"}'
```

O desde el navegador/Postman:
- URL: `http://127.0.0.1:8000/api/auth/validate-user`
- Method: POST
- Headers: `Content-Type: application/json`
- Body: (ver JSON arriba)

---

## 📌 Resumen

**Proyecto afectado:** Juntify (puerto 8000)
**Archivos a crear:**
1. `app/Http/Controllers/Api/AuthValidationController.php`
2. Modificar `routes/api.php`

**Objetivo:** Permitir que Panel DDU valide usuarios contra Juntify verificando que pertenezcan a la empresa DDU.
