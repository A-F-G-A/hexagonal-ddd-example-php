# Seguridad aplicada y límites conocidos

## Controles implementados

- **SQL injection:** PDO con prepared statements. Ordenamiento usa una lista blanca; límites y offsets son parámetros enteros.
- **Validación:** el formulario/API transforma entrada en comandos y el dominio vuelve a validar. IMEI es texto, longitud/dígito Luhn y cantidad SIM se verifica en dominio.
- **XSS:** vistas PHP escapan contenido dinámico con `htmlspecialchars`; JSON usa codificación segura. Los IDs de HTML no sustituyen autorización.
- **CSRF:** token aleatorio por sesión en todos los POST web, incluido logout. API requiere un Bearer token explícito y no autentica cookies de sesión.
- **Sesión:** cookies HttpOnly y SameSite=Lax; Secure cuando HTTPS; strict mode y solo cookies; regeneración en login y cada 15 minutos; expiración por inactividad a los 30 minutos.
- **Autorización:** `RolePermissions` aplica permisos en el servidor tanto en rutas web como API. Alta/edición/eliminación de usuarios es solo `ADMIN`; el registro público no existe.
- **Contraseñas:** `password_hash` bcrypt y `password_verify`; contraseñas no se reponen ni envían en correo. Primer administrador se crea por CLI.
- **Recuperación:** secreto aleatorio de 256 bits; solo se guarda SHA-256; expiración 30 minutos, un solo uso y actualización de contraseña/token atómica. El enlace lleva el token en fragmento URL para que no viaje en la petición GET inicial; referrer policy impide propagarlo.
- **Rate limiting:** límites persistentes MySQL para login por IP y cuenta, solicitud de recuperación por IP y envío de restablecimiento por digest de token. Las claves se almacenan como HMAC con `APP_SECRET`.
- **API:** token aleatorio `cat_…`, almacenamiento SHA-256, cuenta debe estar `ACTIVE`, vencimiento limitado a 365 días, revocación CLI y último uso. Tokens no se muestran de nuevo.
- **Errores/cabeceras:** mensajes internos se sustituyen por una referencia genérica en web/API; logs no incluyen el cuerpo de excepciones. Se envían `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` y `Permissions-Policy`.
- **Secretos:** `.env` se ignora por Git; `.env.example` contiene marcadores. No se usa CORS.

## Límites y trabajo pendiente

- No se ejecutaron pruebas contra MySQL, SMTP ni navegador en esta máquina: no hay cliente/servidor MySQL ni Docker en PATH. La suite local cubre dominio, mapeadores y políticas con doubles, no integración SQL.
- El correo de alta/cambio es notificación de mejor esfuerzo: la operación de usuario persiste aunque SMTP falle; se registra una línea genérica en log y no se envían contraseñas.
- La auditoría de actividad se escribe después de guardar/eliminar y falla de forma no bloqueante; no es una bitácora transaccional resistente a manipulación.
- El rate limit del API aún no tiene cuota por token/IP. Úsalo detrás de un gateway con límites hasta implementarla.
- No hay MFA, recuperación con notificación transaccional/outbox, política de contraseñas configurable, CSP estricta (hay scripts inline), ni detección de anomalías.
- No se configuró una URL pública ni se ejecutó despliegue. Revisa TLS, proxy, headers de HTTPS y backups en el entorno destino.
- No se aplicó la migración. Debe revisarse y respaldarse la base antes de `php bin/migrate.php`; correos duplicados o un esquema legado incompatible requieren remediación previa.

La lista sirve como estado real del repositorio y no es una certificación de cumplimiento OWASP/WCAG.
