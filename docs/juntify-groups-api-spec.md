# ✅ Endpoints Panel DDU - Implementados

## Estado: OPERATIVOS ✓

Se han implementado exitosamente los endpoints en Juntify (puerto 8000) para integración con Panel DDU.

---

## 📍 Endpoints Disponibles

### 1️⃣ Obtener Lista de Usuarios
**GET** `/api/users/list`

**Parámetros opcionales:**
- `search` - Filtrar por username o email
- `exclude_empresa_id` - Excluir usuarios de empresa específica

**Ejemplo:**
```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/users/list?search=juan&exclude_empresa_id=1' -Method GET
```

**Response:**
```json
{
  "success": true,
  "users": [
    {
      "id": "5b324294-6847-4e85-b9f6-1687a9922f75",
      "username": "Administrador_DDU",
      "email": "ddujuntify@gmail.com",
      "name": "Administrador_DDU"
    }
  ],
  "total": 1
}
```

---

### 2️⃣ Añadir Usuario a Empresa
**POST** `/api/users/add-to-company`

**Body:**
```json
{
  "user_id": "5b324294-6847-4e85-b9f6-1687a9922f75",
  "empresa_id": 1,
  "rol": "miembro"
}
```

**Roles permitidos:** `admin`, `miembro`, `administrador`

**Response (201):**
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
      "name": "juan_perez"
    }
  }
}
```

**Errores:**
- `404` - Usuario o empresa no encontrados
- `409` - Usuario ya es integrante de la empresa

---

### 3️⃣ Listar Miembros de Empresa
**GET** `/api/companies/{empresa_id}/members`

**Parámetros opcionales:**
- `include_owner` - Incluir al dueño de la empresa (default: `true`)

**Response (200):**
```json
{
  "success": true,
  "empresa": {
    "id": 3,
    "nombre": "DDU",
    "usuario_principal": "5b324294-6847-4e85-b9f6-1687a9922f75",
    "rol_empresa": "founder"
  },
  "members": [
    {
      "id": "5b324294-6847-4e85-b9f6-1687a9922f75",
      "username": "Administrador_DDU",
      "email": "ddujuntify@gmail.com",
      "name": "Administrador_DDU",
      "is_owner": true,
      "rol": "founder",
      "fecha_agregado": "2026-02-02 16:54:47"
    }
  ],
  "total": 2,
  "stats": {
    "total_members": 2,
    "admins": 1,
    "members": 1
  }
}
```

---

### 4️⃣ Actualizar Rol de Miembro
**PATCH** `/api/companies/{empresa_id}/members/{user_id}/role`

**Body:**
```json
{
  "rol": "admin"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Rol actualizado exitosamente",
  "data": {
    "empresa_id": 3,
    "user_id": "uuid",
    "username": "Jona0327",
    "nuevo_rol": "admin"
  }
}
```

---

### 5️⃣ Eliminar Miembro de Empresa
**DELETE** `/api/companies/{empresa_id}/members/{user_id}`

**Response (200):**
```json
{
  "success": true,
  "message": "Miembro eliminado exitosamente"
}
```

---

### 6️⃣ Obtener Contactos de Usuario
**GET** `/api/users/{user_id}/contacts`

**Response (200):**
```json
{
  "success": true,
  "user": {
    "id": "uuid",
    "username": "Jona0327"
  },
  "contacts": [...],
  "total": 1
}
```

---

### 8️⃣ Obtener Reuniones del Usuario
**GET** `/api/users/{user_id}/meetings`

**Parámetros opcionales:**
- `limit` - Cantidad de reuniones (default: `100`, max: `500`)
- `offset` - Offset para paginación (default: `0`)
- `order_by` - Campo de orden: `created_at`, `meeting_name`, `id`
- `order_dir` - Dirección: `asc` o `desc`

**Response (200):**
```json
{
  "success": true,
  "meetings": [
    {
      "id": 5,
      "meeting_name": "Reunión del 02/02/2026 12:13",
      "username": "Jona0327",
      "transcript": {
        "file_name": "Reunión.ju",
        "file_content": "base64...",
        "encoding": "base64"
      },
      "audio": {
        "file_name": "Reunión.mp3",
        "file_content": "base64...",
        "encoding": "base64"
      }
    }
  ],
  "pagination": {
    "total": 1,
    "limit": 100,
    "offset": 0,
    "has_more": false
  }
}
```

---

### 9️⃣ Obtener Grupos de Reuniones del Usuario
**GET** `/api/users/{user_id}/meeting-groups`

**Response (200):**
```json
{
  "success": true,
  "groups": [],
  "total": 0
}
```

---

### 🔟 Obtener Detalles de Reunión
**GET** `/api/meetings/{meeting_id}`

**Response (200):**
```json
{
  "success": true,
  "meeting": {
    "id": 5,
    "meeting_name": "Reunión del 02/02/2026 12:13",
    "username": "Jona0327",
    "status": "completed"
  }
}
```

---

### 1️⃣1️⃣ Detalles de Reunión (Completo)
**GET** `/api/meetings/{meeting_id}/details`

**Response:** Incluye meeting, container, audio_file, transcription, tasks, permissions

---

### 1️⃣2️⃣ Descargar Archivo de Reunión
**GET** `/api/meetings/{meeting_id}/download/{file_type}`

**Path Parameters:**
- `file_type` - `transcript`, `audio`, o `both`

**Query Parameters:**
- `username` (requerido) - Username del dueño
- `format` - `base64`, `url`, `stream` (default: `base64`)

**Response base64 (200):**
```json
{
  "success": true,
  "file_name": "Reunión.ju",
  "file_content": "base64...",
  "encoding": "base64"
}
```

---

### 1️⃣3️⃣ Tipo de Reunión (Etiqueta)
**GET** `/api/meetings/{meeting_id}/type`

**Tipos disponibles:**
| Tipo | Label | Color |
|------|-------|-------|
| `personal` | Personal | `#8B5CF6` |
| `organizational` | Organizacional | `#3B82F6` |
| `shared` | Compartida | `#10B981` |

**Response (200):**
```json
{
  "success": true,
  "type": "personal",
  "type_label": "Personal",
  "type_color": "#8B5CF6"
}
```

**Batch:** `POST /api/meetings/types` con `{ "meeting_ids": [1, 5, 10] }`

---

## 🏷️ Sistema de Grupos en Empresas

### Tablas de Base de Datos (Juntify_Panels)
- **grupos_empresa** - Grupos dentro de empresas
- **miembros_grupo_empresa** - Miembros con roles
- **reuniones_compartidas_grupo** - Reuniones compartidas con permisos

---

### 🔹 Grupos - CRUD

#### Listar Grupos de una Empresa
**GET** `/api/companies/{empresa_id}/groups`

**Response (200):**
```json
{
  "groups": [
    {
      "id": 1,
      "nombre": "Equipo Desarrollo",
      "descripcion": "Grupo para compartir reuniones",
      "empresa_id": 3,
      "created_by": "Jona0327",
      "miembros": [...],
      "miembros_count": 2,
      "reuniones_compartidas": [...]
    }
  ],
  "total": 1
}
```

---

#### Crear Grupo
**POST** `/api/companies/{empresa_id}/groups`

**Body:**
```json
{
  "nombre": "Equipo Desarrollo",
  "descripcion": "Descripción",
  "created_by": "UUID-del-usuario"
}
```

**Response (201):**
```json
{
  "message": "Grupo creado exitosamente",
  "group": {
    "id": 1,
    "nombre": "Equipo Desarrollo"
  }
}
```

**Nota:** El creador se añade automáticamente como administrador.

---

#### Ver Grupo
**GET** `/api/companies/{empresa_id}/groups/{grupo_id}`

---

#### Actualizar Grupo
**PUT** `/api/companies/{empresa_id}/groups/{grupo_id}`

**Body:**
```json
{
  "nombre": "Nuevo Nombre",
  "descripcion": "Nueva descripción"
}
```

---

#### Eliminar Grupo
**DELETE** `/api/companies/{empresa_id}/groups/{grupo_id}`

---

### 🔹 Miembros de Grupo

#### Añadir Miembro a Grupo
**POST** `/api/groups/{grupo_id}/members`

**Body:**
```json
{
  "user_id": "uuid",
  "rol": "colaborador"
}
```

**Roles:** `administrador`, `colaborador`, `invitado`

---

#### Actualizar Rol de Miembro
**PUT** `/api/groups/{grupo_id}/members/{member_id}`

---

#### Eliminar Miembro de Grupo
**DELETE** `/api/groups/{grupo_id}/members/{member_id}`

---

### 🔹 Compartir Reuniones

#### Compartir Reunión con Grupo
**POST** `/api/groups/{grupo_id}/share-meeting`

**Body:**
```json
{
  "meeting_id": 5,
  "shared_by": "Jona0327",
  "permisos": {
    "ver_audio": true,
    "ver_transcript": true,
    "descargar": true
  },
  "mensaje": "Mensaje opcional"
}
```

**Response (201):**
```json
{
  "message": "Reunión compartida exitosamente",
  "shared_meeting": {
    "id": 1,
    "meeting_id": 5,
    "grupo_id": 1,
    "shared_by": "Jona0327",
    "permisos": {...}
  }
}
```

---

#### Listar Reuniones Compartidas del Grupo
**GET** `/api/groups/{grupo_id}/shared-meetings`

---

#### Dejar de Compartir Reunión
**DELETE** `/api/groups/{grupo_id}/shared-meetings/{meeting_id}`

---

### 🔹 Descargar Archivos de Reunión Compartida ⭐

**GET** `/api/companies/{empresa_id}/groups/{grupo_id}/shared-meetings/{meeting_id}/files`

**Parámetros:**
- `requester_user_id` (requerido) - ID del usuario que solicita
- `file_type` - `transcript`, `audio`, `both` (default: `both`)

Este endpoint usa **autorización delegada** - el token del usuario que compartió.

**Response (200):**
```json
{
  "meeting_id": 5,
  "meeting_name": "Reunión",
  "shared_by": "Jona0327",
  "permisos": {...},
  "can_download": true,
  "transcript": {
    "file_content": "base64...",
    "encoding": "base64"
  },
  "audio": {
    "file_content": "base64...",
    "encoding": "base64"
  }
}
```

---

### 🔹 Grupos del Usuario

**GET** `/api/users/{user_id}/company-groups`

**Response (200):**
```json
{
  "user_id": "uuid",
  "groups": [
    {
      "id": 1,
      "nombre": "Equipo Desarrollo",
      "empresa_id": 3,
      "empresa_nombre": "DDU",
      "rol_en_grupo": "colaborador"
    }
  ],
  "total": 1
}
```

---

## 📊 Resumen de Integración

| Endpoint | Método | Propósito |
|----------|--------|-----------|
| `/api/users/list` | GET | Obtener usuarios disponibles |
| `/api/users/add-to-company` | POST | Registrar integrante en empresa |
| `/api/users/{user_id}/contacts` | GET | Obtener contactos de usuario |
| `/api/users/{user_id}/meetings` | GET | Obtener reuniones del usuario |
| `/api/users/{user_id}/meeting-groups` | GET | Obtener grupos de reuniones |
| `/api/users/{user_id}/company-groups` | GET | Obtener grupos de empresa del usuario |
| `/api/companies/{empresa_id}/members` | GET | Listar miembros de empresa |
| `/api/companies/{empresa_id}/members/{user_id}/role` | PATCH | Actualizar rol de miembro |
| `/api/companies/{empresa_id}/members/{user_id}` | DELETE | Eliminar miembro de empresa |
| `/api/companies/{empresa_id}/groups` | GET | Listar grupos de empresa |
| `/api/companies/{empresa_id}/groups` | POST | Crear grupo |
| `/api/companies/{empresa_id}/groups/{id}` | GET | Ver grupo |
| `/api/companies/{empresa_id}/groups/{id}` | PUT | Actualizar grupo |
| `/api/companies/{empresa_id}/groups/{id}` | DELETE | Eliminar grupo |
| `/api/groups/{grupo_id}/members` | POST | Añadir miembro a grupo |
| `/api/groups/{grupo_id}/members/{id}` | PUT | Actualizar rol de miembro |
| `/api/groups/{grupo_id}/members/{id}` | DELETE | Eliminar miembro de grupo |
| `/api/groups/{grupo_id}/share-meeting` | POST | Compartir reunión con grupo |
| `/api/groups/{grupo_id}/shared-meetings` | GET | Listar reuniones compartidas |
| `/api/groups/{grupo_id}/shared-meetings/{id}` | DELETE | Dejar de compartir reunión |
| `/api/companies/{id}/groups/{g}/shared-meetings/{m}/files` | GET | Descargar archivos compartidos |
| `/api/meetings/{meeting_id}` | GET | Obtener detalles de reunión |
| `/api/meetings/{meeting_id}/details` | GET | Detalles completos de reunión |
| `/api/meetings/{meeting_id}/download/{file_type}` | GET | Descargar archivo |
| `/api/meetings/{meeting_id}/type` | GET | Obtener tipo de reunión |
| `/api/meetings/types` | POST | Obtener tipos batch |
| `/api/auth/validate-user` | POST | Validar credenciales |
| `/api/auth/check-company-membership` | POST | Verificar pertenencia |

---

## ✅ Estado Final

- ✅ Todos los endpoints de usuarios - **FUNCIONANDO**
- ✅ Todos los endpoints de empresas/miembros - **FUNCIONANDO**
- ✅ Todos los endpoints de reuniones - **FUNCIONANDO**
- ✅ Sistema de Grupos en Empresas - **FUNCIONANDO**
  - ✅ CRUD de Grupos
  - ✅ Gestión de Miembros con Roles
  - ✅ Compartir Reuniones con Permisos
  - ✅ Descargar Archivos con Autorización Delegada

**Total: 27 endpoints disponibles para Panel DDU** 🚀

---

**Última actualización:** 02/02/2026
**Servidor:** http://127.0.0.1:8000
