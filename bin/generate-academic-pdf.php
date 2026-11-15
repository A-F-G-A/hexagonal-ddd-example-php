<?php

declare(strict_types=1);

/** Generates the academic hand-in PDF using only PHP core functions. */
$output = dirname(__DIR__) . '/docs/entrega-academica.pdf';
$content = array(
    'ENTREGA ACADEMICA - DESARROLLO WEB',
    'Universidad de Cartagena',
    'Programa: Ingeniería de Software',
    'Asignatura: Desarrollo Web',
    'Semestre: no confirmado',
    'Unidad 2 | Actividad: Ejercicio 11 - Celular',
    'Estudiante: Andrés Felipe Guzmán Arrieta',
    'Repositorio: github.com/A-F-G-A/hexagonal-ddd-example-php',
    'Pull Request: github.com/A-F-G-A/hexagonal-ddd-example-php/pull/5',
    'Video individual: [PENDIENTE - agregar enlace despues de grabar]',
    '',
    'DESCRIPCION DEL SISTEMA',
    'Plataforma web para conservar el CRUD de usuarios y administrar un catalogo tecnico de celulares. Extiende el proyecto docente original sin cambiar el front controller, el cargador propio ni las capas existentes.',
    '',
    'ARQUITECTURA IMPLEMENTADA',
    'Domain: UserModel, Celular, Value Objects y reglas de validacion. Application: servicios, Commands, Queries y puertos de entrada/salida. Infrastructure: controladores web/API y adaptadores PDO MySQL/correo. Common: configuracion y composicion manual.',
    'Web y API comparten casos de uso. CQRS se limita a separar comandos de escritura y consultas; no hay buses ni infraestructura CQRS avanzada.',
    'Navegador / API -> controladores -> servicios Application -> puertos -> adaptadores MySQL',
    '',
    'EJERCICIO 11 - CELULAR: 20 ATRIBUTOS',
    'marca | imei | pulgadas | megapixeles | ram | almacenamientoPrincipal | almacenamientoSecundario | sistemaOperativo | operador | tecnologiaBanda',
    'wifi | bluetooth | camaras | marcaCpu | velocidadCpu | nfc | huella | ir | resisteAgua | cantidadSim',
    'ATRIBUTO | REPRESENTACION | TIPO / REGLA',
    'marca | celulares.marca | VARCHAR(80)',
    'imei | celular_imeis.imei | VARCHAR(15), uno o dos; digito Luhn',
    'pulgadas | celulares.pulgadas | DECIMAL(5,2), maximo 100.00',
    'megapixeles | celulares.megapixeles | DECIMAL(8,2), maximo 100000.00',
    'ram | celulares.ram | SMALLINT UNSIGNED, GB, 1..65535',
    'almacenamientoPrincipal | almacenamiento_principal | SMALLINT UNSIGNED, GB',
    'almacenamientoSecundario | almacenamiento_secundario | SMALLINT UNSIGNED, GB; puede ser cero',
    'sistemaOperativo | sistema_operativo | VARCHAR(80)',
    'operador | celulares.operador | VARCHAR(80)',
    'tecnologiaBanda | tecnologia_banda | VARCHAR(80)',
    'wifi | celulares.wifi | BOOLEAN',
    'bluetooth | celulares.bluetooth | BOOLEAN',
    'camaras | celulares.camaras | TINYINT UNSIGNED, 1..255',
    'marcaCpu | marca_cpu | VARCHAR(80)',
    'velocidadCpu | velocidad_cpu | DECIMAL(6,2), GHz, maximo 1000.00',
    'nfc | celulares.nfc | BOOLEAN',
    'huella | celulares.huella | BOOLEAN',
    'ir | celulares.ir | BOOLEAN',
    'resisteAgua | resiste_agua | BOOLEAN',
    'cantidadSim | cantidad_sim | TINYINT UNSIGNED, 1 o 2; coincide con IMEI',
    'El ID es tecnico y adicional. IMEI se conserva como texto de 15 digitos, valida Luhn, es unico y se guarda en celular_imeis: uno o dos valores relacionados con un celular segun cantidadSim. No se inventa relacion celular-usuario.',
    'Pulgadas, megapixeles y velocidad CPU son decimales. RAM y almacenamientos son SMALLINT UNSIGNED; capacidades en GB. Los booleanos son BOOLEAN; camaras y SIM son enteros pequenos. Maximos: megapixeles 100000.00 y velocidad CPU 1000.00; migracion 003 amplia las columnas a DECIMAL(8,2) y DECIMAL(6,2).',
    '',
    'MODELO RELACIONAL',
    'USERS 1 --- N API_TOKENS | USERS 1 --- N PASSWORD_RESET_TOKENS | USERS 1 --- N ACTIVITY_LOGS',
    'CELULARES 1 --- N CELULAR_IMEIS (uno o dos IMEI por equipo; FK ON DELETE CASCADE). No hay FK celular-usuario.',
    '',
    'FUNCIONALIDADES CRUDL',
    'Usuarios: crear, consultar, actualizar, eliminar y listar con paginacion; roles y estado restringidos en servidor.',
    'Celulares: crear, consultar individual, actualizar, eliminar, listar, buscar, filtrar, ordenar y paginar desde web y API.',
    '',
    'SEGURIDAD',
    'Sesiones web consultan cuenta, rol y estado; authorization_version monotona incrementa ante cambios de email, rol, estado o contrasena, incluso si los valores luego se restauran. El reset tambien incrementa la version.',
    'CSRF en POST web; cookies HttpOnly, SameSite y Secure bajo HTTPS. Contrasenas bcrypt. Bearer token almacenado como SHA-256, revocable y validado contra cuenta activa. API: 300/minuto por IP y 120/minuto por identidad.',
    'La persistencia protege transaccionalmente contra eliminar, degradar o desactivar al ultimo administrador. Se preparo una prueba concurrente con procesos y conexiones separados; no se ejecuto localmente.',
    '',
    'PRUEBAS Y EVIDENCIAS',
    'Suite unitaria php tests/run.php: 78 aserciones aprobadas. No equivalen a cobertura de codigo; repositorios y rate limits usan doubles en memoria.',
    'PHP lint: 128 archivos PHP del proyecto, cero errores. HTTP local: login 200 sin warnings, cookie de sesion y nosniff; API respondio JSON 503 generico al no poder conectar MySQL. Se uso session.save_path temporal por permisos locales.',
    'MySQL: servicio local 26.7.0 y puerto 3306, sin acceso autenticado para listar bases; no se modifico ninguna base. Integracion/concurrencia MySQL: no ejecutada. Docker: no ejecutado; no disponible en PATH.',
    'GitHub Actions declara PHP 8.3, MySQL 8.4, lint, pruebas, migraciones, integracion, build Docker y auditoria OSV. La existencia del YAML no prueba un resultado remoto aprobado.',
    'Evidencia: salidas locales de tests/lint y archivos de codigo/documentacion. No se adjuntan capturas de despliegue ni evidencia visual no producida.',
    '',
    'DOCKER, POSTMAN Y DESPLIEGUE',
    'Dockerfile usa PHP 8.3/Apache, rewrite y AllowOverride. Compose define web, MySQL 8.4, secretos obligatorios y volumen mysql_data. Revisado, no construido ni levantado.',
    'Coleccion: docs/postman_collection.json. Entorno sin credenciales: docs/postman_environment.example.json. api_token esta vacio; requiere uno local emitido por CLI.',
    'URL publica: no configurada. No se crearon servicios cloud ni recursos de pago.',
    '',
    'DATOS PENDIENTES DE INTERVENCION HUMANA',
    'Completar URL individual del video despues de grabar y confirmar semestre. No se inventan tutor, correo institucional, fecha ni enlaces.',
    '',
    'DOCUMENTACION',
    'README.md: alcance y configuracion. docs/architecture.md: arquitectura y limites. docs/data-model.md: esquema y atributos. docs/security.md: controles/pendientes. docs/operations.md: operacion. docs/openapi.yaml: API. docs/guion-sustentacion.md: recorrido individual.'
);

$wrap = static function (string $text, int $width = 88): array {
    if ($text === '') return array('');
    $words = preg_split('/\s+/', trim($text)) ?: array();
    $lines = array();
    $line = '';
    foreach ($words as $word) {
        if ($line !== '' && strlen($line) + strlen($word) + 1 > $width) {
            $lines[] = $line;
            $line = '';
        }
        $line .= ($line === '' ? '' : ' ') . $word;
    }
    if ($line !== '') $lines[] = $line;
    return $lines;
};

$pages = array();
$page = array();
foreach ($content as $paragraph) {
    foreach ($wrap($paragraph) as $line) {
        if (count($page) >= 48) {
            $pages[] = $page;
            $page = array();
        }
        $page[] = $line;
    }
    $page[] = '';
}
if ($page !== array()) $pages[] = $page;

$objects = array();
$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
$kids = array();
$nextObject = 5;
foreach ($pages as $pageNumber => $lines) {
    $pageObject = $nextObject++;
    $streamObject = $nextObject++;
    $kids[] = $pageObject . ' 0 R';
    $text = "BT\n50 750 Td\n14 TL\n";
    foreach ($lines as $line) {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $line);
        $encoded = $encoded === false ? '' : $encoded;
        $encoded = str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), $encoded);
        $heading = $line !== '' && strlen($line) <= 48 && strtoupper($line) === $line;
        $text .= ($heading ? "/F2 11 Tf\n" : "/F1 10 Tf\n") . '(' . $encoded . ") Tj\nT*\n";
    }
    $text .= '(Pagina ' . ($pageNumber + 1) . ' de ' . count($pages) . ") Tj\nET";
    $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $streamObject . ' 0 R >>';
    $objects[$streamObject] = '<< /Length ' . strlen($text) . ">>\nstream\n" . $text . "\nendstream";
}
$objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($pages) . ' >>';
$objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
$objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
ksort($objects);

$pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
$offsets = array(0);
foreach ($objects as $number => $object) {
    $offsets[$number] = strlen($pdf);
    $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
}
$xrefOffset = strlen($pdf);
$size = max(array_keys($objects)) + 1;
$pdf .= "xref\n0 " . $size . "\n0000000000 65535 f \n";
for ($number = 1; $number < $size; $number++) {
    $pdf .= sprintf('%010d 00000 n ', $offsets[$number] ?? 0) . "\n";
}
$pdf .= 'trailer << /Size ' . $size . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";
if (file_put_contents($output, $pdf) === false) {
    fwrite(STDERR, "No se pudo escribir el PDF.\n");
    exit(1);
}
fwrite(STDOUT, 'PDF generado: ' . $output . ' (' . count($pages) . " paginas)\n");
