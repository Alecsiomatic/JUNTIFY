# Integración de archivos .ju de Juntify

Para que Panel DDU pueda leer los archivos `.ju` exportados por Juntify es necesario compartir la misma clave de cifrado:

1. Abre el archivo `.env` del proyecto de Juntify y copia el valor completo de `APP_KEY` (incluyendo el prefijo `base64:` si existe).
2. Péga ese valor en la variable `APP_KEY` del archivo `.env` de Panel DDU.
3. Si cuentas con claves anteriores que aún se utilizan para reuniones antiguas, declara todas separadas por comas en `LEGACY_APP_KEYS` dentro del mismo `.env`.

Con esta configuración el servicio `JuDecryptionService` intentará desencriptar primero con la clave actual de Laravel y, si es necesario, continuará con cada clave listada en `LEGACY_APP_KEYS`.
