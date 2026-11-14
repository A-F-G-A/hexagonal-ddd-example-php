# Arquitectura y decisiones técnicas

## Línea base

El proyecto conserva el front controller PHP, el cargador de clases propio y las capas `Domain`, `Application`, `Infrastructure` y `Common`. La línea base del 14 de noviembre de 2026 contiene 82 archivos PHP sin errores de sintaxis en PHP CLI 8.5.5. No hay Composer, framework, pruebas automatizadas ni esquema de base de datos. El código requiere `PDO`, `pdo_mysql`, `session` y OpenSSL para SMTP. La versión de MySQL del host no se pudo detectar.

## Límites

- **Domain:** reglas, entidades, value objects y eventos; no conoce HTTP ni PDO.
- **Application:** casos de uso, Commands/Queries y puertos.
- **Infrastructure:** adaptadores MySQL, controlador web/API, correo y configuración.
- **Presentation:** vistas PHP, CSS y JavaScript sin consultas ni reglas de negocio.
- **Common:** composición manual y carga de clases, manteniendo el enfoque existente sin introducir un framework.

Las lecturas se modelan como queries y las escrituras como commands. El mismo caso de uso se comparte entre web y API.

## Decisiones para el ejercicio 11 — Celular

La entidad `Celular` conserva los 20 nombres de atributos indicados por el ejercicio; el ID es técnico y adicional. `imei` es una colección de uno o dos identificadores, alineada con `cantidadSim`. Se persiste en una tabla hija normalizada para admitir búsquedas y unicidad por identificador físico sin guardar dos IMEI concatenados en una columna. Cada IMEI es único globalmente porque identifica una interfaz celular concreta; un teléfono dual SIM tiene dos IMEI distintos y ambos apuntan al mismo celular. Se valida que la cantidad de IMEI coincida con `cantidadSim` (1 o 2).

`wifi`, `bluetooth`, `nfc`, `huella`, `ir` y `resisteAgua` son booleanos. `pulgadas`, `megapixeles` y `velocidadCpu` son decimales de precisión fija. `ram`, `almacenamientoPrincipal`, `almacenamientoSecundario`, `camaras` y `cantidadSim` son enteros; almacenamiento secundario puede ser cero cuando no tiene ranura/expansión. Las unidades de capacidad se expresan en GB y la velocidad CPU en GHz.

## Roles y compatibilidad

Roles objetivo: `ADMIN`, `SUPERVISOR`, `OPERADOR` y `CONSULTA`. `ADMIN` administra usuarios, configuración y todo el catálogo; `SUPERVISOR` administra celulares y consulta usuarios; `OPERADOR` crea, consulta y actualiza celulares; `CONSULTA` solo consulta. Los valores legados `MEMBER` y `REVIEWER` se interpretan como `OPERADOR` y `CONSULTA` durante la transición para no bloquear cuentas existentes. Los formularios nunca deciden el rol permitido: se verifica en servidor.

El alta inicial de administrador se realiza con un comando CLI de bootstrap, no con un registro público que permita escalar privilegios. Las migraciones se entregan, pero no se ejecutan automáticamente contra bases existentes.

## Compatibilidad y límites operativos

Se toma PHP 8.3 como mínimo por lo declarado en el README; la sintaxis se mantiene compatible con 8.3. La migración está dirigida a MySQL 8.0.16+ para restricciones `CHECK` aplicadas por el servidor. El host auditado no expone un servidor MySQL ni Docker, así que las pruebas de integración y contenedores deberán ejecutarse cuando esas herramientas estén disponibles.
