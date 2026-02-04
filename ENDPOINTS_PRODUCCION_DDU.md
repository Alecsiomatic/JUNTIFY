# Endpoints Requeridos en Producción para Panel DDU

## Resumen

Este documento describe todos los endpoints que Juntify debe tener implementados para que Panel DDU funcione correctamente en producción.

---

## 1. Autenticación y Usuarios

### 1.1 Login DDU
```
POST /api/ddu/login
```
**Body:**
```json
{
  "email": "usuario@empresa.com",
  "password": "contraseña"
}
```
**Response:**
```json
{
  "success": true,
  "user": {
    "id": "uuid",
    "name": "Nombre",
    "email": "email@empresa.com",
    "username": "username",
    "empresa_id": 1
  },
  "token": "bearer_token"
}
```

### 1.2 Validar Token
```
GET /api/ddu/validate-token
Headers: Authorization: Bearer {token}
```

### 1.3 Obtener Usuario
```
GET /api/users/{userId}
```

---

## 2. Reuniones

### 2.1 Listar Reuniones del Usuario
```
GET /api/users/{userId}/meetings
```
**Query params:**
- `limit` (int, opcional): Máximo de resultados
- `offset` (int, opcional): Paginación
- `order_by` (string, opcional): Campo para ordenar
- `order_dir` (string, opcional): 'asc' o 'desc'

**Response:**
```json
{
  "meetings": [
    {
      "id": 1,
      "meeting_name": "Reunión de ejemplo",
      "username": "owner_username",
      "transcript_drive_id": "google_drive_file_id",
      "audio_drive_id": "google_drive_audio_id",
      "created_at": "2026-01-15T10:00:00Z"
    }
  ],
  "total": 25
}
```

### 2.2 Obtener Detalles de Reunión
```
GET /api/meetings/{meetingId}
```
**Response:**
```json
{
  "meeting": {
    "id": 1,
    "meeting_name": "Reunión de ejemplo",
    "username": "owner_username",
    "transcript_drive_id": "google_drive_file_id",
    "audio_drive_id": "google_drive_audio_id",
    "created_at": "2026-01-15T10:00:00Z"
  }
}
```

### 2.3 Descargar Archivo de Reunión (.ju)
```
GET /api/meetings/{meetingId}/download/transcript
```
**Query params:**
- `username` (string): Username del dueño de la reunión

**Response:**
```json
{
  "success": true,
  "file_content": "base64_encoded_ju_file_content"
}
```

**IMPORTANTE:** Este endpoint debe usar el delegated token del propietario de la reunión para descargar el archivo desde Google Drive.

---

## 3. Grupos de Reuniones

### 3.1 Listar Grupos del Usuario
```
GET /api/users/{userId}/meeting-groups
```
**Query params:**
- `include_members` (bool): Incluir lista de miembros
- `include_meetings_count` (bool): Incluir conteo de reuniones

**Response:**
```json
{
  "groups": [
    {
      "id": 1,
      "name": "Equipo Desarrollo",
      "description": "Grupo del equipo de desarrollo",
      "members_count": 5,
      "meetings_count": 10
    }
  ]
}
```

### 3.2 Obtener Reuniones Compartidas en Grupo
```
GET /api/groups/{groupId}/shared-meetings
```
**Response:**
```json
{
  "meetings": [
    {
      "id": 1,
      "meeting_id": 1,
      "meeting_name": "Reunión compartida",
      "username": "owner_username",
      "transcript_drive_id": "google_drive_file_id",
      "shared_by": "user_who_shared",
      "created_at": "2026-01-15T10:00:00Z"
    }
  ]
}
```

---

## 4. Configuración del Asistente

### 4.1 Obtener Configuración
```
GET /api/ddu/assistant-settings/{userId}
```
**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": "uuid",
    "openai_api_key_configured": true,
    "enable_drive_calendar": true,
    "default_model": "gpt-4o-mini",
    "created_at": "2026-01-01T00:00:00Z",
    "updated_at": "2026-01-15T00:00:00Z"
  }
}
```

### 4.2 Guardar Configuración
```
POST /api/ddu/assistant-settings/{userId}
```
**Body:**
```json
{
  "openai_api_key": "sk-...",
  "enable_drive_calendar": true,
  "default_model": "gpt-4o-mini"
}
```
**IMPORTANTE:** La API key debe almacenarse encriptada en la base de datos.

### 4.3 Obtener API Key (Desencriptada)
```
GET /api/ddu/assistant-settings/{userId}/api-key
```
**Response:**
```json
{
  "success": true,
  "data": {
    "openai_api_key": "sk-..."
  }
}
```
**IMPORTANTE:** Este endpoint devuelve la API key desencriptada para que Panel DDU pueda usarla en llamadas a OpenAI.

### 4.4 Eliminar API Key
```
DELETE /api/ddu/assistant-settings/{userId}/api-key
```

---

## 5. Conversaciones del Asistente

### 5.1 Listar Conversaciones
```
GET /api/ddu/assistant/conversations
```
**Query params:**
- `user_id` (string, requerido): UUID del usuario

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": "uuid",
      "title": "Nueva conversación",
      "messages_count": 5,
      "created_at": "2026-01-15T10:00:00Z",
      "updated_at": "2026-01-15T11:00:00Z"
    }
  ],
  "total": 10
}
```

### 5.2 Crear Conversación
```
POST /api/ddu/assistant/conversations
```
**Body:**
```json
{
  "user_id": "uuid",
  "title": "Nueva conversación"
}
```
**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": "uuid",
    "title": "Nueva conversación",
    "created_at": "2026-01-15T10:00:00Z"
  }
}
```

### 5.3 Obtener Conversación con Mensajes
```
GET /api/ddu/assistant/conversations/{conversationId}
```
**Query params:**
- `user_id` (string, requerido): UUID del usuario

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": "uuid",
    "title": "Mi conversación",
    "messages": [
      {
        "id": 1,
        "role": "system",
        "content": "Eres el asistente...",
        "metadata": null,
        "created_at": "2026-01-15T10:00:00Z"
      },
      {
        "id": 2,
        "role": "user",
        "content": "Hola, necesito ayuda...",
        "metadata": null,
        "created_at": "2026-01-15T10:01:00Z"
      }
    ],
    "documents": [],
    "created_at": "2026-01-15T10:00:00Z"
  }
}
```

### 5.4 Actualizar Conversación
```
PUT /api/ddu/assistant/conversations/{conversationId}
```
**Body:**
```json
{
  "user_id": "uuid",
  "title": "Nuevo título"
}
```

### 5.5 Eliminar Conversación
```
DELETE /api/ddu/assistant/conversations/{conversationId}
```
**Body:**
```json
{
  "user_id": "uuid"
}
```

---

## 6. Mensajes del Asistente

### 6.1 Obtener Mensajes de Conversación
```
GET /api/ddu/assistant/conversations/{conversationId}/messages
```
**Query params:**
- `user_id` (string, requerido): UUID del usuario
- `limit` (int, opcional): Máximo de mensajes (default: 100)
- `offset` (int, opcional): Paginación

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "assistant_conversation_id": 1,
      "role": "user",
      "content": "Mensaje del usuario",
      "metadata": null,
      "created_at": "2026-01-15T10:00:00Z"
    }
  ],
  "pagination": {
    "total": 50,
    "limit": 100,
    "offset": 0
  }
}
```

### 6.2 Agregar Mensaje
```
POST /api/ddu/assistant/conversations/{conversationId}/messages
```
**Body:**
```json
{
  "user_id": "uuid",
  "role": "user|assistant|system|tool",
  "content": "Contenido del mensaje",
  "metadata": {
    "optional": "data"
  }
}
```
**Response:**
```json
{
  "success": true,
  "data": {
    "id": 10,
    "assistant_conversation_id": 1,
    "role": "user",
    "content": "Contenido del mensaje",
    "metadata": null,
    "created_at": "2026-01-15T10:05:00Z"
  }
}
```

---

## 7. Documentos del Asistente

### 7.1 Obtener Documentos de Conversación
```
GET /api/ddu/assistant/conversations/{conversationId}/documents
```
**Query params:**
- `user_id` (string, requerido): UUID del usuario

### 7.2 Subir Documento
```
POST /api/ddu/assistant/conversations/{conversationId}/documents
```
**Content-Type:** multipart/form-data

**Body:**
- `user_id` (string): UUID del usuario
- `file` (file): Archivo a subir
- `extracted_text` (string, opcional): Texto extraído del documento
- `summary` (string, opcional): Resumen del documento

### 7.3 Eliminar Documento
```
DELETE /api/ddu/assistant/conversations/{conversationId}/documents/{documentId}
```
**Body:**
```json
{
  "user_id": "uuid"
}
```

---

## 8. Tablas de Base de Datos Requeridas

### 8.1 ddu_assistant_settings
```sql
CREATE TABLE ddu_assistant_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL UNIQUE,
    openai_api_key TEXT NULL,  -- Encriptada
    enable_drive_calendar BOOLEAN DEFAULT TRUE,
    default_model VARCHAR(50) DEFAULT 'gpt-4o-mini',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_user_id (user_id)
);
```

### 8.2 ddu_assistant_conversations
```sql
CREATE TABLE ddu_assistant_conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    title VARCHAR(255) DEFAULT 'Nueva conversación',
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);
```

### 8.3 ddu_assistant_messages
```sql
CREATE TABLE ddu_assistant_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assistant_conversation_id BIGINT UNSIGNED NOT NULL,
    role ENUM('system', 'user', 'assistant', 'tool') NOT NULL,
    content LONGTEXT NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (assistant_conversation_id) 
        REFERENCES ddu_assistant_conversations(id) ON DELETE CASCADE,
    INDEX idx_conversation_id (assistant_conversation_id),
    INDEX idx_created_at (created_at)
);
```

### 8.4 ddu_assistant_documents
```sql
CREATE TABLE ddu_assistant_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assistant_conversation_id BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NULL,
    size BIGINT UNSIGNED DEFAULT 0,
    extracted_text LONGTEXT NULL,
    summary TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (assistant_conversation_id) 
        REFERENCES ddu_assistant_conversations(id) ON DELETE CASCADE,
    INDEX idx_conversation_id (assistant_conversation_id)
);
```

---

## 9. Flujo de Funcionamiento

### 9.1 Carga del Asistente
1. Panel DDU llama `GET /api/ddu/assistant-settings/{userId}` para verificar configuración
2. Llama `GET /api/ddu/assistant/conversations?user_id={userId}` para listar conversaciones
3. Llama `GET /api/users/{userId}/meetings` para obtener reuniones propias
4. Llama `GET /api/users/{userId}/meeting-groups` para obtener grupos
5. Por cada grupo, llama `GET /api/groups/{groupId}/shared-meetings` para reuniones compartidas

### 9.2 Envío de Mensaje al Asistente
1. Usuario selecciona reuniones y escribe mensaje
2. Panel DDU llama `POST /api/ddu/assistant/conversations/{id}/messages` para guardar mensaje del usuario
3. Por cada reunión seleccionada:
   - Llama `GET /api/meetings/{meetingId}` para obtener detalles
   - Llama `GET /api/meetings/{meetingId}/download/transcript` para obtener archivo .ju
4. Panel DDU desencripta el .ju y construye el contexto
5. Llama `GET /api/ddu/assistant-settings/{userId}/api-key` para obtener API key de OpenAI
6. Envía request a OpenAI con el contexto
7. Guarda respuesta con `POST /api/ddu/assistant/conversations/{id}/messages`

### 9.3 Descarga de Archivo .ju (Reuniones Compartidas)
Cuando el usuario consulta una reunión compartida en un grupo:
1. Panel DDU llama `GET /api/meetings/{meetingId}/download/transcript?username={ownerUsername}`
2. **Juntify debe usar el delegated token del propietario** para descargar desde Google Drive
3. Retorna el contenido en base64 a Panel DDU
4. Panel DDU desencripta localmente y extrae el contenido

---

## 10. Notas Importantes

1. **Encriptación de API Keys:** La API key de OpenAI debe almacenarse encriptada en Juntify y solo desencriptarse cuando Panel DDU la solicite.

2. **Delegated Tokens:** Para reuniones compartidas, Juntify debe usar el token de Google del propietario de la reunión para descargar archivos de Drive.

3. **Validación de Permisos:** Todos los endpoints deben validar que el `user_id` tenga permisos sobre los recursos solicitados.

4. **Eliminación en Cascada:** Al eliminar una conversación, se deben eliminar sus mensajes y documentos asociados.

5. **Timeouts:** 
   - Endpoints normales: 10-15 segundos
   - Descarga de archivos: 60 segundos

---

## 11. Checklist de Implementación

- [ ] `POST /api/ddu/login`
- [ ] `GET /api/ddu/validate-token`
- [ ] `GET /api/users/{userId}`
- [ ] `GET /api/users/{userId}/meetings`
- [ ] `GET /api/meetings/{meetingId}`
- [ ] `GET /api/meetings/{meetingId}/download/transcript`
- [ ] `GET /api/users/{userId}/meeting-groups`
- [ ] `GET /api/groups/{groupId}/shared-meetings`
- [ ] `GET /api/ddu/assistant-settings/{userId}`
- [ ] `POST /api/ddu/assistant-settings/{userId}`
- [ ] `GET /api/ddu/assistant-settings/{userId}/api-key`
- [ ] `DELETE /api/ddu/assistant-settings/{userId}/api-key`
- [ ] `GET /api/ddu/assistant/conversations`
- [ ] `POST /api/ddu/assistant/conversations`
- [ ] `GET /api/ddu/assistant/conversations/{id}`
- [ ] `PUT /api/ddu/assistant/conversations/{id}`
- [ ] `DELETE /api/ddu/assistant/conversations/{id}`
- [ ] `GET /api/ddu/assistant/conversations/{id}/messages`
- [ ] `POST /api/ddu/assistant/conversations/{id}/messages`
- [ ] `GET /api/ddu/assistant/conversations/{id}/documents`
- [ ] `POST /api/ddu/assistant/conversations/{id}/documents`
- [ ] `DELETE /api/ddu/assistant/conversations/{id}/documents/{docId}`
