# Implementación de Conexiones DDU

## Resumen

Se implementaron dos conexiones de base de datos para manejar la autenticación de usuarios DDU:

### 1. Conexiones de Base de Datos

**Configuración en `.env`:**
```env
# Base de datos principal de Juntify
JUNTIFY_DB_HOST=82.197.93.18
JUNTIFY_DB_PORT=3306
JUNTIFY_DB_DATABASE=juntify_new
JUNTIFY_DB_USERNAME=root
JUNTIFY_DB_PASSWORD=Jona@0327801

# Base de datos de Panel de Empresas (Juntify_Panels)
PANELS_DB_HOST=82.197.93.18
PANELS_DB_PORT=3306
PANELS_DB_DATABASE=Juntify_Panels
PANELS_DB_USERNAME=root
PANELS_DB_PASSWORD=Jona@0327801
```

**Conexiones en `config/database.php`:**
- `juntify`: Conexión a la BD principal donde están los usuarios
- `juntify_panels`: Conexión a la BD de paneles donde están las empresas y miembros

### 2. Modelos Implementados

**Empresa.php**
- Conecta a `juntify_panels.empresa`
- Maneja las empresas registradas
- Método `isDdu()` para verificar si es la empresa DDU

**IntegrantesEmpresa.php**
- Conecta a `juntify_panels.integrantes_empresa`
- Maneja los miembros de empresas
- Métodos principales:
  - `isDduMember($userId)`: Verifica si un usuario es miembro DDU
  - `getDduMembership($userId)`: Obtiene info de membresía DDU
  - `getAllDduMembersWithUsers()`: Lista todos los miembros DDU con su info de usuario

### 3. Servicio de Autenticación

**AuthService.php**
- `validateDduUser($email, $password)`: Valida credenciales y membresía DDU
- `isDduUser($email)`: Verifica si un email pertenece a un usuario DDU

### 4. Flujo de Autenticación Actualizado

**LoginRequest.php:**
1. Busca usuario en BD `juntify` por email
2. Valida contraseña usando `password_verify()`
3. Verifica membresía DDU en BD `juntify_panels`
4. Crea/actualiza usuario local con datos sincronizados
5. Inicia sesión con usuario local

**AuthenticatedSessionController.php:**
- Verificación adicional de seguridad post-login
- Usa las nuevas conexiones para validar membresía DDU

**Middleware EnsureDduMember.php:**
- Protege rutas verificando membresía DDU en tiempo real
- Usa conexión `juntify` para obtener usuario
- Usa conexión `juntify_panels` para verificar membresía

### 5. Comando de Prueba

**TestDduConnections.php:**
```bash
php artisan ddu:test-connections
php artisan ddu:test-connections --email=usuario@ejemplo.com
```

Prueba:
- Conectividad a ambas bases de datos
- Existencia de empresa DDU
- Listado de miembros DDU
- Validación de usuario específico (opcional)

## Uso

### Para probar las conexiones:
```bash
php artisan ddu:test-connections
```

### Para verificar un usuario específico:
```bash
php artisan ddu:test-connections --email=usuario@ddu.com
```

### El login ahora:
1. Consulta usuarios en `juntify_new`
2. Verifica membresía DDU en `Juntify_Panels`
3. Solo permite acceso a miembros verificados de DDU
4. Sincroniza datos entre ambas bases

## Estructura de Tablas

**juntify_panels.empresa:**
- `id`: ID único de empresa
- `iduser`: UUID del usuario fundador (FK a juntify.users)
- `nombre_empresa`: Nombre (debe ser 'DDU')
- `rol`: Rol del fundador
- `es_administrador`: Boolean

**juntify_panels.integrantes_empresa:**
- `id`: ID único del miembro
- `iduser`: UUID del usuario (FK a juntify.users)
- `empresa_id`: ID de empresa (FK a empresa.id)
- `rol`: Rol del miembro en la empresa
- `permisos`: JSON con permisos específicos
