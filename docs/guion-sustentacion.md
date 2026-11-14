# Guion de sustentación

## Apertura

Presentar el objetivo: extender el CRUD PHP de usuarios existente para administrar el ejercicio 11 — Celular. Indicar que se conservaron el cargador de clases, la composición manual y las capas Domain, Application e Infrastructure.

## Recorrido de la aplicación

1. Iniciar el entorno con Docker Compose y confirmar que MySQL está saludable.
2. Aplicar `php bin/migrate.php` y crear el primer administrador con `php bin/create-admin.php`.
3. Iniciar sesión y mostrar el dashboard, el menú condicionado por rol y la paginación de usuarios.
4. Crear un celular, mostrar sus veinte atributos, editarlo, buscarlo por marca/IMEI y eliminarlo.
5. Mostrar validación Luhn y la regla de un IMEI por ranura SIM; probar que no se acepta un IMEI duplicado.
6. Mostrar el acceso de solo lectura con `CONSULTA` y el acceso de operación con `OPERADOR`.
7. Emitir un token de API por CLI y consultar `GET /api/v1/celulares`; mostrar el esquema OpenAPI y la colección Postman. Revocar el token al finalizar.

## Decisiones técnicas

- El dominio `Celular` valida datos sin depender de HTTP o PDO; Commands/Queries se comparten entre controladores web y API.
- Los IMEI se guardan en una tabla hija normalizada porque un celular puede tener una o dos SIM y cada identificador tiene unicidad propia.
- Las operaciones de celular y sus IMEI se guardan en transacciones; las migraciones se aplican explícitamente y el runner registra versiones.
- Los permisos se verifican en servidor. Tokens API y de restablecimiento se persisten como hashes; las sesiones tienen CSRF, cookies endurecidas, vencimiento y rate limit.
- Se mantiene el CRUD de usuarios y se protege la creación de administradores con una herramienta CLI.

## Verificación y límites

Ejecutar `php tests/run.php` (resultado local actual: 61 aserciones) y el lint de PHP (123 archivos de la aplicación y herramientas, sin errores). En el entorno Docker ejecutar `php bin/migrate.php` seguido de `php tests/integration.php`. La base de datos debe ser desechable antes de ejecutar las pruebas de integración.

La ejecución de MySQL, Docker y el despliegue se debe demostrar en un entorno que tenga esos servicios. No afirmar que existe una URL pública ni presentar una captura que no se haya obtenido.

## Cierre

Resumir cómo la solución preserva el proyecto docente y permite evolucionar nuevas entidades mediante dominio, puertos, casos de uso y adaptadores.
