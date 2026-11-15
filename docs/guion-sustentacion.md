# Guion de sustentación

## Apertura

Presentar el objetivo: extender el CRUD PHP de usuarios existente para administrar el ejercicio 11 — Celular. Indicar que se conservaron el cargador de clases, la composición manual y las capas Domain, Application e Infrastructure.

## Recorrido de la aplicación

1. Si Docker y una base descartable están disponibles, iniciar el entorno y confirmar salud antes de continuar; no afirmar que esta ejecución se probó en el host de desarrollo.
2. Aplicar `php bin/migrate.php` solo a la base aprobada y crear el primer administrador con las variables protegidas de `php bin/create-admin.php`.
3. Iniciar sesión y mostrar el dashboard, el menú condicionado por rol y la paginación de usuarios.
4. Crear un celular, mostrar sus veinte atributos, editarlo, buscarlo por marca/IMEI y eliminarlo.
5. Mostrar validación Luhn y la regla de un IMEI por ranura SIM; probar que no se acepta un IMEI duplicado.
6. Mostrar el acceso de solo lectura con `CONSULTA` y el acceso de operación con `OPERADOR`.
7. Emitir un token de API por CLI y consultar `GET /api/v1/celulares`; mostrar el esquema OpenAPI y la colección Postman. Revocar el token al finalizar.

## Decisiones técnicas

- El dominio `Celular` valida datos sin depender de HTTP o PDO; Commands/Queries se comparten entre controladores web y API.
- El ejercicio define veinte atributos. `imei` contiene uno o dos IMEI de 15 dígitos y Luhn; se relaciona con celular por la tabla hija. No se agregó una relación celular-usuario que la guía no pide.
- Los IMEI se guardan en una tabla hija normalizada porque un celular puede tener una o dos SIM y cada identificador tiene unicidad propia.
- Las operaciones de celular y sus IMEI se guardan en transacciones; las migraciones se aplican explícitamente y el runner registra versiones.
- Las lecturas y escrituras se separan con Queries y Commands, sin buses CQRS. Los permisos se verifican en servidor. Tokens API y de restablecimiento se persisten como hashes; las sesiones usan CSRF, cookies endurecidas, vencimiento y una versión monotónica que se incrementa ante cambios de seguridad.
- API Bearer: cada petición verifica vigencia y estado de la cuenta; el rol actual controla permisos. Hay revocación de token, límites por IP e identidad y respuestas JSON. La IP depende de la configuración confiable del proxy.
- La protección transaccional del último administrador y su prueba con procesos/conexiones separadas están implementadas; la prueba de concurrencia solo se puede presentar como ejecutada después de correr `tests/integration.php` en MySQL real descartable.
- Se mantiene el CRUD de usuarios y se protege la creación de administradores con una herramienta CLI.

## Cómo explicar los principios

- **Hexagonal:** los casos de uso dependen de puertos; MySQL y HTTP son adaptadores reemplazables alrededor de la aplicación.
- **DDD:** `Celular` valida sus invariantes, `Imei` protege el formato/Luhn y los Value Objects de usuario encapsulan formato e identidad.
- **CQRS:** Commands describen escrituras y Queries lecturas; no hay un bus CQRS. Web/API reutilizan los servicios.
- **SOLID:** responsabilidades distintas por capas, dependencias hacia abstracciones y puertos pequeños. Explicar el ejemplo concreto, sin afirmar que todo el sistema está certificado.

## Verificación y límites

Resultado local comprobado: `php tests/run.php` informó 78 aserciones correctas; el lint cubrió 128 archivos PHP sin errores. Dos escenarios HTTP se verificaron: la pantalla de login respondió 200 sin warnings y el API respondió JSON 503 genérico al faltar conexión MySQL. No se ejecutaron pruebas de integración ni concurrencia MySQL, Docker build/compose o despliegue. El servicio MYSQL267 no permitió autenticación sin credenciales; no se modificaron bases.

Colección Postman: `docs/postman_collection.json`; importar el entorno de ejemplo y añadir un token real localmente, sin exportarlo. No afirmar que existe una URL pública ni presentar una captura que no se haya obtenido.

## Cierre

Resumir cómo la solución preserva el proyecto docente y permite evolucionar nuevas entidades mediante dominio, puertos, casos de uso y adaptadores.
