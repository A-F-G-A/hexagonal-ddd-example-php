# Seguridad aplicada y límites conocidos

## Controles implementados

- **SQL injection:** PDO con prepared statements. Ordenamiento usa una lista blanca; límites y offsets son parámetros enteros.
- **Validación:** el formulario/API transforma entrada en comandos y el dominio vuelve a validar. IMEI es texto, longitud/dígito Luhn y cantidad SIM se verifica en dominio.
- **XSS:** vistas PHP escapan contenido dinámico con `htmlspecialchars`; JSON usa codificación segura. Los IDs de HTML no sustituyen autorización.
- **CSRF:** token aleatorio por sesión en todos los POST web, incluido logout. API requiere un Bearer token explícito y no autentica cookies de sesión.
- **Sesión:** cookies HttpOnly y SameSite=Lax; Secure cuando HTTPS; strict mode y solo cookies; regeneración en login y cada 15 minutos; expiración por inactividad a los 30 minutos. En cada petición web autenticada se consulta la cuenta actual; un cambio de rol, estado, contraseña, borrado o falta de fingerprint revoca la sesión. Si la persistencia falla, la petición se cierra con 503.
- **Autorización:** `RolePermissions` aplica permisos en el servidor tanto en rutas web como API. Alta/edición/eliminación de usuarios es solo `ADMIN`; el registro público no existe.
- **Contraseñas:** `password_hash` bcrypt y `password_verify`; contraseñas no se reponen ni envían en correo. Primer administrador se crea por CLI.
- **Recuperación:** secreto aleatorio de 256 bits; solo se guarda SHA-256; expiración 30 minutos, un solo uso y actualización de contraseña/token atómica. El enlace lleva el token en fragmento URL para que no viaje en la petición GET inicial; referrer policy impide propagarlo.
- **Rate limiting:** límites persistentes MySQL para login por IP y cuenta, solicitud de recuperación por IP y envío de restablecimiento por digest de token. Las claves se almacenan como HMAC con `APP_SECRET`.
- **API:** token aleatorio `cat_…`, almacenamiento SHA-256, cuenta debe estar `ACTIVE`, vencimiento limitado a 365 días, revocación CLI y último uso. Tokens no se muestran de nuevo. Las solicitudes se limitan por IP (300/minuto) y por identidad autenticada (120/minuto); el límite persistente usa claves HMAC y responde 429 con `Retry-After`.
- **Último administrador:** la persistencia bloquea las filas de administradores activos dentro de una transacción antes de eliminar, desactivar o cambiar el rol, y rechaza la operación si dejaría cero administradores activos.
- **Errores/cabeceras:** mensajes internos se sustituyen por una referencia genérica en web/API; logs no incluyen el cuerpo de excepciones. Se envían `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` y `Permissions-Policy`.
- **Secretos:** `.env` se ignora por Git; `.env.example` contiene marcadores. No se usa CORS.

## Límites y trabajo pendiente

- No se ejecutó la suite de integración MySQL: el servicio Windows `MYSQL267` está activo, pero no se confirmó que la base configurada sea descartable y el script inserta datos. Docker no está disponible. Tampoco se ejecutaron pruebas SMTP ni de navegador. La suite local cubre dominio, mapeadores y políticas con doubles, no integración SQL.
- El correo de alta/cambio es notificación de mejor esfuerzo: la operación de usuario persiste aunque SMTP falle; se registra una línea genérica en log y no se envían contraseñas.
- La auditoría de actividad se escribe después de guardar/eliminar y falla de forma no bloqueante; no es una bitácora transaccional resistente a manipulación.
- El límite por IP usa `REMOTE_ADDR`; detrás de un proxy inverso debe configurarse la red de forma que PHP reciba una IP de cliente confiable. No se confía directamente en cabeceras `X-Forwarded-For` que el cliente puede falsificar. Las cuotas son límites operativos iniciales y deben ajustarse con métricas del despliegue.
- No hay MFA, recuperación con notificación transaccional/outbox, política de contraseñas configurable, CSP estricta (hay scripts inline), ni detección de anomalías.
- No se configuró una URL pública ni se ejecutó despliegue. Revisa TLS, proxy, headers de HTTPS y backups en el entorno destino.
- No se aplicó la migración. Debe revisarse y respaldarse la base antes de `php bin/migrate.php`; correos duplicados o un esquema legado incompatible requieren remediación previa.

La lista sirve como estado real del repositorio y no es una certificación de cumplimiento OWASP/WCAG.
