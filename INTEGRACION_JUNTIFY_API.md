# Integración Panel DDU ↔ Juntify API

## ✅ Implementación Completada

Se ha implementado exitosamente la integración entre Panel DDU (puerto 8001) y Juntify (puerto 8000) mediante consumo de endpoints API.

---

## 📦 Archivos Creados

### 1. Servicio API
**Archivo:** `app/Services/JuntifyApiService.php`

**Métodos disponibles:**
- `getUsersList($search, $excludeEmpresaId)` - Obtener usuarios de Juntify
- `addUserToCompany($userId, $empresaId, $rol)` - Añadir usuario a empresa DDU  
- `getMeetingDetails($meetingId, $userId)` - Obtener detalles completos de reunión
- `validateUser($email, $password, $nombreEmpresa)` - Validar autenticación (ya existente)

### 2. Controlador de Miembros
**Archivo:** `app/Http/Controllers/MembersManagementController.php`

**Rutas:**
- `GET /admin/members` - Vista de gestión de miembros
- `GET /admin/members/search` - Búsqueda AJAX de usuarios
- `POST /admin/members/add` - Añadir usuario a DDU

### 3. Controlador de Reuniones (Extendido)
**Archivo:** `app/Http/Controllers/MeetingDetailsController.php`

**Nuevo método:**
- `showFromJuntify($meetingId)` - Obtener detalles desde Juntify API

**Ruta:**
- `GET /api/meetings/{meetingId}/details` - Detalles completos de reunión

---

## 🔌 Endpoints Panel DDU Disponibles

### 1️⃣ Gestión de Miembros

#### Listar página de miembros
```http
GET http://127.0.0.1:8001/admin/members
```

#### Buscar usuarios disponibles (AJAX)
```http
GET http://127.0.0.1:8001/admin/members/search?search=juan
```

**Response:**
```json
{
  "success": true,
  "users": [
    {
      "id": "uuid",
      "username": "juan_perez",
      "email": "juan@example.com",
      "name": "Juan Pérez"
    }
  ],
  "total": 1
}
```

#### Añadir usuario a DDU
```http
POST http://127.0.0.1:8001/admin/members/add
Content-Type: application/json

{
  "user_id": "5b324294-6847-4e85-b9f6-1687a9922f75",
  "rol": "miembro"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Usuario añadido exitosamente a DDU",
  "data": { ... }
}
```

---

### 2️⃣ Detalles de Reuniones

```http
GET http://127.0.0.1:8001/api/meetings/{meetingId}/details?user_id={userId}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "meeting": { ... },
    "container": { ... },
    "audio_file": {
      "filename": "meeting_uuid.ju",
      "file_path": "/path/to/file.ju",
      "encrypted": true,
      "google_drive_file_id": "...",
      "download_url": "https://drive.google.com/..."
    },
    "transcription": { ... },
    "tasks": [ ... ],
    "permissions": { ... }
  }
}
```

---

## ⚙️ Configuración

### Archivo `.env`
```dotenv
# Juntify API Configuration
JUNTIFY_API_URL=http://127.0.0.1:8000/api
```

### Rutas Protegidas
Todas las rutas están protegidas con el middleware `juntify.auth`:

```php
Route::middleware(['juntify.auth'])->group(function () {
    // Gestión de miembros
    Route::prefix('admin/members')->name('admin.members.')->group(function () {
        Route::get('/', [MembersManagementController::class, 'index']);
        Route::get('/search', [MembersManagementController::class, 'searchUsers']);
        Route::post('/add', [MembersManagementController::class, 'addMember']);
    });

    // Detalles de reuniones
    Route::get('/api/meetings/{meetingId}/details', [MeetingDetailsController::class, 'showFromJuntify']);
});
```

---

## 🧪 Pruebas de Integración

### Test 1: Obtener usuarios disponibles
```powershell
# Desde Panel DDU - Requiere estar autenticado
# Acceder en el navegador:
http://127.0.0.1:8001/admin/members
```

### Test 2: Añadir usuario a DDU
```javascript
// Desde consola del navegador en Panel DDU
fetch('http://127.0.0.1:8001/admin/members/add', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        user_id: '5b324294-6847-4e85-b9f6-1687a9922f75',
        rol: 'miembro'
    })
})
.then(r => r.json())
.then(console.log);
```

### Test 3: Obtener detalles de reunión
```javascript
// Desde consola del navegador en Panel DDU
fetch('http://127.0.0.1:8001/api/meetings/MEETING_ID_HERE/details')
    .then(r => r.json())
    .then(console.log);
```

---

## 📊 Flujo de Datos

```
┌─────────────────┐           ┌──────────────────┐           ┌──────────────────┐
│   Panel DDU     │           │  JuntifyApiService│           │  Juntify Server  │
│  (puerto 8001)  │           │                  │           │  (puerto 8000)   │
└────────┬────────┘           └────────┬─────────┘           └────────┬─────────┘
         │                             │                              │
         │ 1. Solicitud del usuario    │                              │
         ├────────────────────────────>│                              │
         │                             │                              │
         │                             │ 2. HTTP Request              │
         │                             ├─────────────────────────────>│
         │                             │                              │
         │                             │ 3. JSON Response             │
         │                             │<─────────────────────────────┤
         │                             │                              │
         │ 4. Datos procesados         │                              │
         │<────────────────────────────┤                              │
         │                             │                              │
```

---

## 🔐 Autenticación

### Session-Based Auth
Panel DDU usa autenticación basada en sesión con Juntify:

1. Usuario inicia sesión en `/login`
2. `JuntifyLoginController` valida contra `POST /api/auth/validate-user`
3. Datos de usuario y empresa se almacenan en sesión:
   ```php
   Session::put('authenticated', true);
   Session::put('juntify_user', $userData);
   Session::put('juntify_company', $companyData);
   ```
4. Middleware `CheckJuntifyAuth` verifica sesión en cada request

---

## 📋 Endpoints Juntify Consumidos

| Endpoint | Método | Usado por |
|----------|--------|-----------|
| `/api/auth/validate-user` | POST | JuntifyLoginController |
| `/api/users/list` | GET | MembersManagementController |
| `/api/users/add-to-company` | POST | MembersManagementController |
| `/api/meetings/{id}/details` | GET | MeetingDetailsController |

---

## 🚀 Características Implementadas

✅ **Gestión de Miembros**
- Búsqueda de usuarios de Juntify
- Filtrado por nombre, username o email
- Añadir usuarios a empresa DDU con rol específico
- Validación de duplicados (409 Conflict)

✅ **Detalles de Reuniones**
- Información completa de reuniones
- Datos del contenedor asociado
- Archivo .ju (audio encriptado)
- Transcripciones completas
- Tareas asignadas con detalles
- Permisos del usuario

✅ **Manejo de Errores**
- Timeouts configurables
- Logging de errores
- Mensajes amigables al usuario
- Códigos HTTP apropiados

✅ **Seguridad**
- Middleware de autenticación
- Verificación de permisos
- CSRF Protection
- Validación de datos

---

## 📌 Próximos Pasos

- [ ] Implementar paginación en lista de usuarios
- [ ] Añadir filtros avanzados (por rol, empresa)
- [ ] Cache de respuestas frecuentes
- [ ] Rate limiting en cliente
- [ ] Tests automatizados

---

## 🛠️ Comandos Útiles

```bash
# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Ver rutas disponibles
php artisan route:list | grep -E "members|meetings"

# Logs en tiempo real
tail -f storage/logs/laravel.log
```

---

**Última actualización:** 02/02/2026  
**Estado:** ✅ OPERATIVO  
**Versión:** 1.0
