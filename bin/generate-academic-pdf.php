<?php

declare(strict_types=1);

/** Generates the academic hand-in PDF using only PHP core functions. */
$output = dirname(__DIR__) . '/docs/entrega-academica.pdf';
$content = array(
    'PORTADA',
    'Universidad de Cartagena',
    'Programa: Ingenieria de Software',
    'Asignatura: Desarrollo Web',
    'Unidad 2',
    'Actividad: Ejercicio 11 - Celular',
    'Estudiante: Andres Felipe Guzman Arrieta',
    '',
    'DESCRIPCION',
    'Plataforma web para gestionar usuarios y un catalogo tecnico de celulares. El trabajo evoluciona el CRUD PHP del repositorio docente y conserva la arquitectura hexagonal y sus capas originales.',
    '',
    'ARQUITECTURA',
    'Domain contiene entidades, value objects y reglas. Application contiene servicios, Commands, Queries y puertos. Infrastructure adapta HTTP, MySQL y correo. Common conserva la carga de clases y composicion manual. El mismo caso de uso se utiliza desde la web y la API.',
    '',
    'MODELO DE DATOS',
    'La tabla users conserva las cuentas del CRUD previo. celulares almacena los atributos escalares y celular_imeis representa uno o dos IMEI por equipo. La clave primaria IMEI evita duplicados globales; la relacion en cascada elimina sus filas al borrar un celular. Tokens API, restablecimientos de clave, rate limits y actividad tienen tablas separadas.',
    '',
    'FUNCIONALIDADES',
    'CRUD de usuarios con paginacion y administracion restringida. CRUD de celulares con veinte atributos, validacion Luhn, compatibilidad con una o dos SIM, filtros, busqueda, orden y paginacion. Dashboard, roles, API REST Bearer, historial de actividad, Docker Compose y pipeline CI.',
    '',
    'SEGURIDAD',
    'Permisos comprobados en servidor. Formularios protegidos con CSRF; cookies de sesion HttpOnly y SameSite; regeneracion y expiracion de sesion; rate limiting de autenticacion; tokens temporales de recuperacion de un solo uso; contrasenas con bcrypt. Los tokens API se guardan como hashes.',
    '',
    'PRUEBAS Y EVIDENCIAS',
    'La suite php tests/run.php usa doubles para reglas de dominio, permisos, servicios y mapeo. La verificacion final local registra 61 aserciones correctas y lint sin errores en 123 archivos PHP de la aplicacion y herramientas. Una solicitud HTTP local a la vista de inicio de sesion respondio 200 e incluyo el token CSRF y cabeceras de seguridad. GitHub Actions incluye lint, pruebas unitarias, migracion en MySQL efimero, pruebas de integracion y construccion Docker.',
    'En este entorno no hay servidor MySQL ni Docker disponibles. Por ello, las pruebas de integracion, la migracion real y la construccion de imagen quedan pendientes de ejecucion en CI o en un host con dichos servicios.',
    '',
    'REPOSITORIO Y DESPLIEGUE',
    'Repositorio publico esperado: https://github.com/AFGA/hexagonal-ddd-example-php',
    'URL de despliegue: no configurada.',
    'Enlaces de sustentacion: no disponibles.',
    '',
    'DATOS ACADEMICOS NO CONFIRMADOS',
    'No se incluye semestre porque no fue confirmado. No se agregan tutor, correo institucional, fecha ni enlaces no proporcionados.',
    '',
    'DOCUMENTACION',
    'README.md describe configuracion y ejecucion. docs/architecture.md registra las decisiones, docs/data-model.md el esquema, docs/security.md los controles y limitaciones, docs/openapi.yaml el contrato API y docs/guion-sustentacion.md propone el recorrido de demostracion.'
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
$nextObject = 4;
foreach ($pages as $pageNumber => $lines) {
    $pageObject = $nextObject++;
    $streamObject = $nextObject++;
    $kids[] = $pageObject . ' 0 R';
    $text = "BT\n/F1 10 Tf\n50 750 Td\n14 TL\n";
    foreach ($lines as $line) {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $line);
        $encoded = $encoded === false ? '' : $encoded;
        $encoded = str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), $encoded);
        $text .= '(' . $encoded . ") Tj\nT*\n";
    }
    $text .= '(Pagina ' . ($pageNumber + 1) . ' de ' . count($pages) . ") Tj\nET";
    $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $streamObject . ' 0 R >>';
    $objects[$streamObject] = '<< /Length ' . strlen($text) . ">>\nstream\n" . $text . "\nendstream";
}
$objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($pages) . ' >>';
$objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
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
