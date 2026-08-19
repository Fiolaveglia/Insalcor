<?php
/**
 * Importa los productos de Nutrición Animal desde los CSV exportados de
 * "Productos Nutrición Animal .xlsx" (una pestaña = una especie).
 *
 *   php data/import_nutricion.php --dir=../../import-data --dry-run
 *   php data/import_nutricion.php --dir=../../import-data
 *
 * Opciones:
 *   --dir=RUTA     Carpeta con los CSV. Default: ../import-data respecto del repo.
 *   --dry-run      No escribe nada; imprime el reporte de lo que haría.
 *   --force        Pisa los campos ya cargados a mano. Sin esto sólo completa
 *                  los campos que están vacíos en la base.
 *   --estado=X     draft | published | auto (default: auto = published si tiene
 *                  descripción, draft si no).
 *   --csv=RUTA     Además, vuelca las filas normalizadas a un CSV para revisar.
 *
 * La importación es idempotente: la identidad de una ficha es
 * (nombre, area_negocio, especie), así que se puede correr las veces que haga falta.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/bootstrap.php';
require_once __DIR__ . '/migrate.php';

const AREA_IMPORT = 'Nutricion Animal';

/** Nombre de la pestaña => valor de `especie` en la base. */
const MAPA_ESPECIES = [
    'LECHERIA'  => 'Lechería',
    'GANADERIA' => 'Ganadería',
    'MASCOTAS'  => 'Mascotas',
    'AVES'      => 'Aves',
    'CERDOS'    => 'Porcinos',
    'EQUINOS'   => 'Equinos',
    'OVINOS'    => 'Ovinos',
];

/**
 * Variantes de nombre que son el mismo producto en distintas pestañas.
 * Se usan SÓLO para propagar la descripción entre pestañas: el `nombre` que se
 * guarda es siempre el que figura en la planilla.
 * variante normalizada => nombre canónico normalizado
 */
const ALIAS_NOMBRES = [
    'MINERALES HIDROXI (ALTA BIODISPONIBILIDAD)' => 'INTELLIBOND MINERALES HIDROXI (ALTA BIODISPONIBILIDAD)',
    'SUPRAMULIN PS'                              => 'SUPRAMULIN',
    'LINCOFARM TR'                               => 'LINCOFARM',
];

/**
 * Erratas de la planilla que sí se corrigen en el nombre que se guarda, porque
 * el nombre se muestra en el sitio. Se listan al final de la importación.
 * clave normalizada => nombre corregido
 */
const CORRECCIONES_NOMBRE = [
    'CALSEA POWER ADVANSE'                                  => 'CALSEA POWER ADVANCE',
    'MINERALES HIDROXI (ALTA BIODIPONIBILIDAD)'             => 'MINERALES HIDROXI (ALTA BIODISPONIBILIDAD)',
    'INTELLIBOND MINERALES HIDROXI (ALTA BIODIPONIBILIDAD)' => 'INTELLIBOND MINERALES HIDROXI (ALTA BIODISPONIBILIDAD)',
];

/* --------------------------------------------------------------- utilidades */

function arg_valor(array $argv, string $nombre, ?string $default = null): ?string
{
    foreach ($argv as $a) {
        if (str_starts_with($a, "--$nombre=")) {
            return substr($a, strlen($nombre) + 3);
        }
    }
    return $default;
}

function arg_flag(array $argv, string $nombre): bool
{
    return in_array("--$nombre", $argv, true);
}

/** Clave de comparación de nombres: mayúsculas, sin acentos, espacios colapsados. */
function clave_nombre(string $s): string
{
    $s = mb_strtoupper(trim($s), 'UTF-8');
    $s = strtr($s, [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
    ]);
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return trim($s);
}

/** Clave canónica: aplica ALIAS_NOMBRES sobre clave_nombre(). */
function clave_canonica(string $s): string
{
    $k = clave_nombre($s);
    return ALIAS_NOMBRES[$k] ?? $k;
}

/** "SI"/"NO" de la planilla => 1/0. Cualquier otra cosa => null (sin dato). */
function si_no(string $v): ?int
{
    $v = clave_nombre($v);
    if ($v === 'SI' || $v === 'S' || $v === 'X') {
        return 1;
    }
    if ($v === 'NO' || $v === 'N') {
        return 0;
    }
    return null;
}

/** ¿Es una nota "REPLICAR EN ..." y no contenido real? */
function es_nota_replicar(string $v): bool
{
    return (bool) preg_match('/^\s*REPLICAR\b/iu', $v);
}

/**
 * Texto plano de la planilla => HTML seguro para `productos.descripcion`
 * (que se imprime sin escapar en product-single.php).
 * Los renglones que empiezan con "-" se agrupan en una <ul>.
 */
function texto_a_html(string $texto): string
{
    $texto = trim(str_replace(["\r\n", "\r"], "\n", $texto));
    if ($texto === '') {
        return '';
    }

    $html = '';
    $parrafo = [];
    $lista = [];

    $cerrarParrafo = static function () use (&$parrafo, &$html): void {
        if ($parrafo) {
            $html .= '<p>' . implode(' ', $parrafo) . '</p>';
            $parrafo = [];
        }
    };
    $cerrarLista = static function () use (&$lista, &$html): void {
        if ($lista) {
            $html .= '<ul><li>' . implode('</li><li>', $lista) . '</li></ul>';
            $lista = [];
        }
    };

    foreach (explode("\n", $texto) as $linea) {
        $linea = trim($linea);
        if ($linea === '') {
            $cerrarParrafo();
            $cerrarLista();
            continue;
        }
        $esItem = (bool) preg_match('/^[-–•]\s*/u', $linea);
        $contenido = htmlspecialchars(
            preg_replace('/^[-–•]\s*/u', '', $linea) ?? $linea,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        if ($esItem) {
            $cerrarParrafo();
            $lista[] = $contenido;
        } else {
            $cerrarLista();
            $parrafo[] = $contenido;
        }
    }
    $cerrarParrafo();
    $cerrarLista();

    // La planilla separa cada viñeta con una línea en blanco; eso cortaría la
    // lista en una <ul> por ítem. Las volvemos a unir.
    return str_replace('</ul><ul>', '', $html);
}

/* ------------------------------------------------------------ lectura CSV */

/**
 * Lee un CSV de la planilla y devuelve las filas normalizadas.
 *
 * El layout no es igual en todas las pestañas (algunas tienen una columna vacía
 * a la izquierda y un encabezado "Especie:"), así que se ubica la fila de
 * encabezado buscando la celda "Nombre" y se leen las columnas por posición
 * relativa a ella: nombre, descripción, categoría/nota, foto, ficha, blog,
 * producto terminado.
 *
 * @return array{filas: array<int, array<string, mixed>>, avisos: string[]}
 */
function leer_csv(string $ruta, string $pestania): array
{
    $fh = fopen($ruta, 'rb');
    if ($fh === false) {
        throw new RuntimeException("No se pudo abrir $ruta");
    }

    $filas = [];
    $avisos = [];
    $col0 = null;   // índice de la columna "Nombre"
    $nroFila = 0;

    while (($campos = fgetcsv($fh, 0, ',', '"', '')) !== false) {
        $nroFila++;
        $campos = array_map(static fn ($c) => trim((string) $c), $campos);

        if ($col0 === null) {
            $idx = array_search('Nombre', $campos, true);
            if ($idx !== false) {
                $col0 = (int) $idx;
            }
            continue; // todo lo anterior al encabezado se descarta
        }

        $celda = static fn (int $offset): string => $campos[$col0 + $offset] ?? '';

        $nombre = $celda(0);
        if ($nombre === '') {
            continue; // filas separadoras
        }

        $descripcion = $celda(1);
        $categoria   = $celda(2);
        $notas       = [];

        // En MASCOTAS la nota "REPLICAR ..." quedó escrita en la columna
        // Descripción; no es contenido del producto.
        if (es_nota_replicar($descripcion)) {
            $notas[] = $descripcion;
            $descripcion = '';
        }
        if ($categoria !== '') {
            if (es_nota_replicar($categoria)) {
                $notas[] = $categoria;
            } elseif (in_array($categoria, CATEGORIAS, true)) {
                // La planilla no usa categorías reales hoy, pero si algún día
                // las carga las respetamos.
                $notas['categoria'] = $categoria;
            } else {
                $avisos[] = "$pestania fila $nroFila ($nombre): categoría desconocida «{$categoria}», se guarda como nota.";
                $notas[] = $categoria;
            }
        }

        $categoriaReal = is_string($notas['categoria'] ?? null) ? $notas['categoria'] : '';
        unset($notas['categoria']);

        foreach ([3 => 'foto', 4 => 'ficha', 5 => 'blog', 6 => 'terminado'] as $off => $etiqueta) {
            $bruto = $celda($off);
            if ($bruto !== '' && si_no($bruto) === null) {
                $avisos[] = "$pestania fila $nroFila ($nombre): valor no reconocido «{$bruto}» en columna $etiqueta, se ignora.";
            }
        }

        $nombreLimpio = preg_replace('/\s+/u', ' ', $nombre);
        $corregido = CORRECCIONES_NOMBRE[clave_nombre($nombreLimpio)] ?? null;
        if ($corregido !== null && $corregido !== $nombreLimpio) {
            $avisos[] = "$pestania: se corrigió el nombre «{$nombreLimpio}» → «{$corregido}».";
            $nombreLimpio = $corregido;
        }

        $filas[] = [
            'pestania'           => $pestania,
            'fila'               => $nroFila,
            'nombre'             => $nombreLimpio,
            'especie'            => MAPA_ESPECIES[$pestania],
            'descripcion'        => $descripcion,
            'categoria'          => $categoriaReal,
            'nota_replicar'      => implode(' | ', $notas),
            'tiene_imagen'       => si_no($celda(3)),
            'tiene_ficha'        => si_no($celda(4)),
            'tiene_blog'         => si_no($celda(5)),
            'producto_terminado' => si_no($celda(6)),
        ];
    }
    fclose($fh);

    if ($col0 === null) {
        $avisos[] = "$pestania: no se encontró la fila de encabezado (celda «Nombre»); archivo omitido.";
    }

    return ['filas' => $filas, 'avisos' => $avisos];
}

/**
 * Ubica un CSV por pestaña dentro de la carpeta. Ignora copias tipo
 * "... GANADERIA (1).csv".
 */
function csv_por_pestania(string $dir): array
{
    $encontrados = [];
    foreach (glob(rtrim($dir, '/') . '/*.csv') ?: [] as $ruta) {
        $base = pathinfo($ruta, PATHINFO_FILENAME);
        if (preg_match('/\(\d+\)\s*$/', $base)) {
            continue; // duplicado del descargador
        }
        foreach (array_keys(MAPA_ESPECIES) as $pestania) {
            if (str_contains(clave_nombre($base), $pestania)) {
                $encontrados[$pestania] = $ruta;
                break;
            }
        }
    }
    return $encontrados;
}

/* ----------------------------------------------------------------- proceso */

/** Busca la carpeta import-data en el repo o hasta 3 niveles por encima. */
function dir_import_por_defecto(): string
{
    $base = root_path();
    for ($i = 0; $i <= 3; $i++) {
        if (is_dir($base . '/import-data')) {
            return $base . '/import-data';
        }
        $base = dirname($base);
    }
    return root_path() . '/import-data';
}

$dir = arg_valor($argv, 'dir', dir_import_por_defecto());
$dryRun = arg_flag($argv, 'dry-run');
$force = arg_flag($argv, 'force');
$estadoOpt = arg_valor($argv, 'estado', 'auto');
$csvSalida = arg_valor($argv, 'csv');

if (!in_array($estadoOpt, ['auto', 'draft', 'published'], true)) {
    fwrite(STDERR, "--estado debe ser auto, draft o published\n");
    exit(1);
}
if (!is_dir($dir)) {
    fwrite(STDERR, "No existe la carpeta: $dir\n");
    exit(1);
}

$archivos = csv_por_pestania($dir);
if (!$archivos) {
    fwrite(STDERR, "No se encontró ningún CSV de pestaña conocida en $dir\n");
    exit(1);
}

$filas = [];
$avisos = [];
foreach ($archivos as $pestania => $ruta) {
    $r = leer_csv($ruta, $pestania);
    $filas = array_merge($filas, $r['filas']);
    $avisos = array_merge($avisos, $r['avisos']);
}

// --- 1. Propagación de descripciones entre pestañas -------------------------
// La planilla escribe la descripción una sola vez (con la nota "REPLICAR EN
// ...") y la deja vacía en el resto de las pestañas donde aparece el mismo
// producto. Nos quedamos con la descripción más completa por producto.
$mejorDescripcion = [];
foreach ($filas as $f) {
    if ($f['descripcion'] === '') {
        continue;
    }
    $k = clave_canonica($f['nombre']);
    if (!isset($mejorDescripcion[$k]) || mb_strlen($f['descripcion']) > mb_strlen($mejorDescripcion[$k])) {
        $mejorDescripcion[$k] = $f['descripcion'];
    }
}

$heredadas = 0;
foreach ($filas as &$f) {
    $f['descripcion_heredada'] = false;
    if ($f['descripcion'] === '') {
        $k = clave_canonica($f['nombre']);
        if (isset($mejorDescripcion[$k])) {
            $f['descripcion'] = $mejorDescripcion[$k];
            $f['descripcion_heredada'] = true;
            $heredadas++;
        }
    }
    $f['descripcion_html'] = texto_a_html($f['descripcion']);
}
unset($f);

// --- 2. Duplicados dentro de la misma pestaña ------------------------------
$vistos = [];
$unicas = [];
foreach ($filas as $f) {
    $k = $f['especie'] . '|' . clave_nombre($f['nombre']);
    if (isset($vistos[$k])) {
        $avisos[] = sprintf(
            '%s fila %d: «%s» ya aparece en la fila %d de la misma pestaña; se omite la repetición.',
            $f['pestania'], $f['fila'], $f['nombre'], $vistos[$k]
        );
        continue;
    }
    $vistos[$k] = $f['fila'];
    $unicas[] = $f;
}
$filas = $unicas;

// --- 3. Nombres donde uno contiene al otro ---------------------------------
// Suele indicar variantes de un mismo producto que la planilla escribe distinto
// (ej. "A MAX" / "A MAX ULTRA"). Sólo se avisa si además falta la descripción
// en uno de los dos: ahí es donde conviene revisar si hay que sumar un alias.
$conDescripcion = [];
$claves = [];
foreach ($filas as $f) {
    $k = clave_nombre($f['nombre']);
    $claves[$k] = true;
    if ($f['descripcion'] !== '' && !$f['descripcion_heredada']) {
        $conDescripcion[$k] = true;
    }
}
$claves = array_keys($claves);
sort($claves);

$parecidos = [];
foreach ($claves as $i => $a) {
    foreach (array_slice($claves, $i + 1) as $b) {
        if (!str_contains($b, $a) || isset(ALIAS_NOMBRES[$a]) || isset(ALIAS_NOMBRES[$b])) {
            continue;
        }
        if (isset($conDescripcion[$a]) === isset($conDescripcion[$b])) {
            continue; // los dos tienen texto propio, o ninguno: nada que copiar
        }
        $falta = isset($conDescripcion[$a]) ? $b : $a;
        $parecidos[] = "«{$a}» ⊂ «{$b}» — «{$falta}» quedó sin descripción propia.";
    }
}

// --- 4. Una ficha por producto, con N especies -----------------------------
// El mismo producto aparece en varias pestañas: se consolida en un solo
// registro y las pestañas se vuelven filas de producto_especies.
$productos = [];
foreach ($filas as $f) {
    $k = clave_nombre($f['nombre']);
    if (!isset($productos[$k])) {
        $productos[$k] = [
            'nombre'             => $f['nombre'],
            'descripcion_html'   => $f['descripcion_html'],
            'categoria'          => $f['categoria'],
            'nota_replicar'      => $f['nota_replicar'],
            'tiene_imagen'       => $f['tiene_imagen'],
            'tiene_ficha'        => $f['tiene_ficha'],
            'tiene_blog'         => $f['tiene_blog'],
            'producto_terminado' => $f['producto_terminado'],
            'pestanias'          => [],
            'especies'           => [],
        ];
    }
    $p = &$productos[$k];
    // Gana la descripción más larga; el resto de los campos, el primer dato útil.
    if (mb_strlen($f['descripcion_html']) > mb_strlen($p['descripcion_html'])) {
        $p['descripcion_html'] = $f['descripcion_html'];
    }
    $p['categoria'] = $p['categoria'] !== '' ? $p['categoria'] : $f['categoria'];
    $p['nota_replicar'] = $p['nota_replicar'] !== '' ? $p['nota_replicar'] : $f['nota_replicar'];
    foreach (['tiene_imagen', 'tiene_ficha', 'tiene_blog', 'producto_terminado'] as $col) {
        $p[$col] ??= $f[$col];
    }
    $p['pestanias'][] = $f['pestania'];
    $p['especies'][$f['especie']] = true;
    unset($p);
}
foreach ($productos as &$p) {
    $p['especies'] = array_keys($p['especies']);
}
unset($p);

// --- 5. Escritura ----------------------------------------------------------
// La migración de esquema corre siempre (es aditiva e idempotente); --dry-run
// sólo evita escribir filas de productos.
foreach (migrar(db()) as $l) {
    echo "[migración] $l\n";
}

$selUno = db()->prepare('SELECT * FROM productos WHERE nombre = ? AND area_negocio = ? LIMIT 1');
$insert = db()->prepare(
    'INSERT INTO productos
       (nombre, descripcion, area_negocio, categoria, estado,
        tiene_imagen, tiene_ficha, tiene_blog, producto_terminado,
        nota_replicar, origen, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$selEspecies = db()->prepare('SELECT especie FROM producto_especies WHERE producto_id = ?');
$insEspecie = db()->prepare('INSERT OR IGNORE INTO producto_especies (producto_id, especie) VALUES (?, ?)');

$insertados = 0;
$actualizados = 0;
$sinCambios = 0;
$especiesAgregadas = 0;
$detalle = [];

if (!$dryRun) {
    db()->beginTransaction();
}

foreach ($productos as $p) {
    $estado = $estadoOpt === 'auto'
        ? ($p['descripcion_html'] !== '' ? 'published' : 'draft')
        : $estadoOpt;
    $especiesTxt = implode(', ', $p['especies']);

    $selUno->execute([$p['nombre'], AREA_IMPORT]);
    $existente = $selUno->fetch();

    if (!$existente) {
        if (!$dryRun) {
            $insert->execute([
                $p['nombre'], $p['descripcion_html'], AREA_IMPORT, $p['categoria'], $estado,
                $p['tiene_imagen'], $p['tiene_ficha'], $p['tiene_blog'], $p['producto_terminado'],
                $p['nota_replicar'], 'import:' . implode('+', array_unique($p['pestanias'])), now_sql(),
            ]);
            $nuevoId = (int) db()->lastInsertId();
            foreach ($p['especies'] as $e) {
                $insEspecie->execute([$nuevoId, $e]);
            }
        }
        $insertados++;
        $especiesAgregadas += count($p['especies']);
        $detalle[] = sprintf('  + %-50s [%s] %s', $p['nombre'], $estado, $especiesTxt);
        continue;
    }

    $productoId = (int) $existente['id'];

    // Las especies siempre se suman (nunca se quitan): si alguien agregó una a
    // mano en el admin, una re-importación no debe borrarla.
    $selEspecies->execute([$productoId]);
    $yaTiene = array_column($selEspecies->fetchAll(), 'especie');
    $faltan = array_values(array_diff($p['especies'], $yaTiene));
    if ($faltan && !$dryRun) {
        foreach ($faltan as $e) {
            $insEspecie->execute([$productoId, $e]);
        }
    }
    $especiesAgregadas += count($faltan);

    // Sin --force sólo completamos lo que está vacío: no pisamos ediciones
    // hechas a mano desde el admin.
    $cambios = [];
    $set = static function (string $col, $valor, bool $vacioActual) use (&$cambios, $force): void {
        if ($force || $vacioActual) {
            $cambios[$col] = $valor;
        }
    };

    if ($p['descripcion_html'] !== '') {
        $set('descripcion', $p['descripcion_html'], trim((string) $existente['descripcion']) === '');
    }
    if ($p['categoria'] !== '') {
        $set('categoria', $p['categoria'], (string) $existente['categoria'] === '');
    }
    if ($p['nota_replicar'] !== '') {
        $set('nota_replicar', $p['nota_replicar'], (string) ($existente['nota_replicar'] ?? '') === '');
    }
    foreach (['tiene_imagen', 'tiene_ficha', 'tiene_blog', 'producto_terminado'] as $col) {
        if ($p[$col] !== null) {
            $set($col, $p[$col], ($existente[$col] ?? null) === null);
        }
    }

    if (!$cambios && !$faltan) {
        $sinCambios++;
        continue;
    }

    if ($cambios && !$dryRun) {
        $sql = 'UPDATE productos SET ' . implode(', ', array_map(static fn ($c) => "$c = ?", array_keys($cambios)))
            . ', updated_at = ? WHERE id = ?';
        $stmt = db()->prepare($sql);
        $stmt->execute([...array_values($cambios), now_sql(), $productoId]);
    }
    $actualizados++;
    $notas = array_keys($cambios);
    if ($faltan) {
        $notas[] = '+especies: ' . implode(', ', $faltan);
    }
    $detalle[] = sprintf('  ~ %-50s (%s)', $p['nombre'], implode(', ', $notas));
}

if (!$dryRun) {
    db()->commit();
}

// --- 6. CSV de control opcional -------------------------------------------
if ($csvSalida !== null) {
    $fh = fopen($csvSalida, 'wb');
    fputcsv($fh, ['nombre', 'especies', 'descripcion', 'categoria', 'nota_replicar',
                  'tiene_imagen', 'tiene_ficha', 'tiene_blog', 'producto_terminado']);
    foreach ($productos as $p) {
        fputcsv($fh, [
            $p['nombre'], implode(', ', $p['especies']), $p['descripcion_html'],
            $p['categoria'], $p['nota_replicar'],
            $p['tiene_imagen'], $p['tiene_ficha'], $p['tiene_blog'], $p['producto_terminado'],
        ]);
    }
    fclose($fh);
}

/* ----------------------------------------------------------------- reporte */

$modo = $dryRun ? 'SIMULACIÓN (--dry-run, no se escribió nada)' : 'IMPORTACIÓN APLICADA';
echo "\n== $modo ==\n";
echo 'Carpeta: ' . $dir . "\n";
echo 'Pestañas leídas: ' . implode(', ', array_keys($archivos)) . "\n\n";

$porEspecie = [];
foreach ($productos as $p) {
    foreach ($p['especies'] as $esp) {
        $porEspecie[$esp] = ($porEspecie[$esp] ?? 0) + 1;
    }
}
foreach ($porEspecie as $esp => $n) {
    printf("  %-12s %3d productos\n", $esp, $n);
}

printf("\nFilas leídas: %d → %d fichas | nuevas: %d | actualizadas: %d | sin cambios: %d\n",
    count($filas), count($productos), $insertados, $actualizados, $sinCambios);
printf("Vínculos producto↔especie agregados: %d\n", $especiesAgregadas);
printf("Descripciones heredadas de otra pestaña (notas REPLICAR): %d\n", $heredadas);

$sinDescripcion = [];
foreach ($productos as $p) {
    if ($p['descripcion_html'] === '') {
        $sinDescripcion[clave_nombre($p['nombre'])] = $p['especies'];
    }
}
printf("Productos sin descripción en ninguna pestaña: %d\n", count($sinDescripcion));

if ($detalle) {
    echo "\n-- Detalle --\n" . implode("\n", $detalle) . "\n";
}
if ($sinDescripcion) {
    echo "\n-- Falta descripción en la planilla (quedan en '"
        . ($estadoOpt === 'auto' ? 'draft' : $estadoOpt) . "') --\n";
    foreach ($sinDescripcion as $nombre => $especies) {
        printf("  · %-45s %s\n", $nombre, implode(', ', $especies));
    }
}
if ($avisos) {
    echo "\n-- Avisos --\n";
    foreach (array_unique($avisos) as $a) {
        echo "  ! $a\n";
    }
}
if ($parecidos) {
    echo "\n-- Nombres a revisar --\n";
    foreach (array_unique($parecidos) as $p) {
        echo "  ? $p\n";
    }
}
echo "\n";
