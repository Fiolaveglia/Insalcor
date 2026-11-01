<?php
/**
 * Importa el catálogo Pharma / VetPharma / Suplementos desde los CSV exportados
 * de "Pharma Productos pagina WEB.xlsx" (una pestaña = un CSV; el nombre del
 * archivo termina en " - <Pestaña>.csv", como los exporta Google Sheets).
 *
 *   php data/import_pharma.php --dir=/ruta/a/los/csv --dry-run
 *   php data/import_pharma.php --dir=/ruta/a/los/csv
 *
 * Opciones:
 *   --dir=RUTA     Carpeta con los CSV (obligatorio).
 *   --dry-run      No escribe nada; imprime el reporte de lo que haría.
 *   --force        Pisa descripción, categoría, subgrupo y países ya cargados.
 *                  Sin esto sólo completa lo que está vacío y suma países.
 *   --estado=X     published | draft, para las fichas NUEVAS (default:
 *                  published). Las existentes conservan su estado.
 *
 * Cómo se lee cada pestaña (ver PESTANIAS):
 *   - Las columnas se buscan por NOMBRE de encabezado, no por posición: el
 *     encabezado está en la fila 1 o 2 según la pestaña, y se escribe distinto
 *     ("Pais" / "PAIS").
 *   - APIS TODOS: la columna "Uso" decide el área (Humano → Pharma,
 *     Veterinario → VetPharma) y "Grupo terapéutico" es el subgrupo.
 *   - Las pestañas sin "Uso" (Excipiente, Capsulas, ...) van a Pharma Y
 *     VetPharma: una ficha por área, como el resto del catálogo.
 *   - Las filas "API" de la pestaña Excipiente se omiten: los APIs salen de
 *     APIS TODOS, que es la que tiene el grupo terapéutico (si no, quedarían
 *     duplicados).
 *   - Vitaminas: la categoría del sitio es "Vitaminas" y el tipo de vitamina
 *     (liposoluble / hidrosoluble / vitaminoide) pasa a ser el subgrupo. Lo
 *     mismo para las vitaminas de la pestaña Suplementos.
 *
 * Cualquier valor desconocido (país, categoría, uso) aborta ANTES de escribir,
 * indicando pestaña y fila. La importación es idempotente: la identidad de una
 * ficha es (nombre, nombre_comercial, representada, área).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/public.php'; // normalizar_texto(), constantes
require_once __DIR__ . '/migrate.php';

/**
 * Encabezados aceptados para cada campo (comparados con normalizar_texto(),
 * así "PAIS", "Pais " y "País" son lo mismo).
 */
const ENCABEZADOS = [
    'pais'             => ['pais'],
    'nombre'           => ['nombre (del compuesto)', 'nombre del compuesto'],
    'representada'     => ['representada / otros'],
    'categoria'        => ['categoria', 'categoria: excipientes o api'],
    'nombre_comercial' => ['titulo para desplegable - nombre comercial'],
    'descripcion'      => ['descripcion', 'descripcion breve del uso'],
    'uso'              => ['uso'],
    'grupo'            => ['grupo terapeutico'],
];

/** Tipos de vitamina: en el sitio son el subgrupo de la categoría "Vitaminas". */
const TIPOS_VITAMINA = ['Vitamina liposoluble', 'Vitamina hidrosoluble', 'Vitaminoide'];

/**
 * Pestañas conocidas (nombre normalizado => configuración):
 *   campos     columnas obligatorias además de pais, nombre y descripcion;
 *   areas      áreas fijas, o null si las decide la columna "Uso";
 *   categoria  valor de la columna Categoría (normalizado) => categoría del
 *              sitio; null = omitir la fila; '*vitamina' = Vitaminas + subgrupo.
 */
const PESTANIAS = [
    'excipiente' => [
        'campos' => ['representada', 'categoria', 'nombre_comercial'],
        'areas' => ['Pharma', 'VetPharma'],
        'categoria' => ['excipiente' => 'Excipientes', 'api' => null],
    ],
    'apis todos' => [
        'campos' => ['categoria', 'uso', 'grupo'],
        'areas' => null,
        // Las 6 filas "Excipiente"/"Otro" de esta pestaña quedan en APIs, bajo
        // el grupo "Excipientes y auxiliares" en el que las puso la planilla.
        'categoria' => ['api' => 'APIs', 'excipiente' => 'APIs', 'otro' => 'APIs'],
    ],
    'capsulas' => [
        'campos' => ['representada', 'categoria', 'nombre_comercial'],
        'areas' => ['Pharma', 'VetPharma'],
        'categoria' => ['capsula' => 'Cápsulas'],
    ],
    'material de empaque' => [
        'campos' => ['representada', 'categoria', 'nombre_comercial'],
        'areas' => ['Pharma', 'VetPharma'],
        'categoria' => ['empaque' => 'Material de empaque'],
    ],
    'recubrimientos' => [
        'campos' => ['representada', 'categoria', 'nombre_comercial'],
        'areas' => ['Pharma', 'VetPharma'],
        'categoria' => ['recubrimiento' => 'Recubrimiento'],
    ],
    'nutricion celular' => [
        'campos' => ['representada', 'categoria', 'nombre_comercial'],
        'areas' => ['Pharma', 'VetPharma'],
        'categoria' => ['nutricion celular' => 'Nutrición celular'],
    ],
    'vitaminas' => [
        'campos' => ['representada', 'categoria', 'nombre_comercial'],
        'areas' => ['Pharma', 'VetPharma'],
        'categoria' => ['vitamina liposoluble' => '*vitamina', 'vitamina hidrosoluble' => '*vitamina', 'vitaminoide' => '*vitamina'],
    ],
    'suplementos' => [
        'campos' => ['representada', 'categoria', 'nombre_comercial'],
        'areas' => ['Suplementos'],
        'categoria' => [
            'activos' => 'Activos', 'proteinas' => 'Proteínas', 'creatinas' => 'Creatinas',
            'aminoacidos' => 'Aminoácidos', 'extractos' => 'Extractos',
            'hidratos de carbono' => 'Hidratos de carbono', 'grasas' => 'Grasas',
            'minerales' => 'Minerales',
            'vitamina liposoluble' => '*vitamina', 'vitamina hidrosoluble' => '*vitamina', 'vitaminoide' => '*vitamina',
        ],
    ],
];

const USO_A_AREA = ['humano' => 'Pharma', 'veterinario' => 'VetPharma'];

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

/** Texto de una celda: sin BOM, sin espacios de más. */
function limpiar(string $s): string
{
    $s = str_replace("\u{FEFF}", '', $s);
    return trim((string) preg_replace('/\s+/u', ' ', $s));
}

/** Clave de comparación de encabezados y valores: minúsculas, sin acentos. */
function clave(string $s): string
{
    return normalizar_texto(limpiar($s));
}

/** Valor de un mapa normalizado => canónico (ej. TIPOS_VITAMINA). */
function canonico(string $valor, array $canonicos): ?string
{
    foreach ($canonicos as $c) {
        if (clave($c) === clave($valor)) {
            return $c;
        }
    }
    return null;
}

/**
 * "TODOS" => los tres países; "URUGUAY / PARAGUAY" => dos. Lanza si aparece un
 * país que no está en PAISES.
 *
 * @return string[]
 */
function parsear_paises(string $celda): array
{
    if (clave($celda) === 'todos') {
        return PAISES;
    }
    $paises = [];
    foreach (explode('/', $celda) as $parte) {
        if (limpiar($parte) === '') {
            continue;
        }
        $pais = canonico($parte, PAISES);
        if ($pais === null) {
            throw new UnexpectedValueException("país desconocido «" . limpiar($parte) . "»");
        }
        $paises[] = $pais;
    }
    if (!$paises) {
        throw new UnexpectedValueException('sin país');
    }
    return array_values(array_unique($paises));
}

/** Descripción de la planilla (texto plano) => HTML seguro de un párrafo. */
function descripcion_html(string $texto): string
{
    $texto = limpiar($texto);
    return $texto === '' ? '' : '<p>' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</p>';
}

/** "Pharma Productos pagina WEB version 3.xlsx - APIS TODOS.csv" => "apis todos". */
function pestania_de(string $archivo): string
{
    $base = basename($archivo, '.csv');
    $pos = strrpos($base, ' - ');
    return clave($pos === false ? $base : substr($base, $pos + 3));
}

/* -------------------------------------------------------------- lectura */

/**
 * Lee una pestaña y la convierte en fichas listas para la base (una por área).
 *
 * @return array{fichas: array<int, array<string, mixed>>, omitidas: string[]}
 */
function leer_pestania(string $ruta, string $pestania, array $config): array
{
    $fh = fopen($ruta, 'rb');
    if ($fh === false) {
        throw new RuntimeException("No se pudo abrir $ruta");
    }

    $columnas = null;   // campo => índice
    $fichas = [];
    $omitidas = [];
    $nroFila = 0;
    $requeridos = array_merge(['pais', 'nombre', 'descripcion'], $config['campos']);

    while (($campos = fgetcsv($fh, 0, ',', '"', '')) !== false) {
        $nroFila++;
        $campos = array_map(static fn ($c) => limpiar((string) $c), $campos);

        // Todo lo anterior al encabezado se descarta. El encabezado es la
        // primera fila que tiene las columnas de país y nombre.
        if ($columnas === null) {
            $claves = array_map('clave', $campos);
            $encontradas = [];
            foreach (ENCABEZADOS as $campo => $aceptados) {
                foreach ($aceptados as $a) {
                    $idx = array_search($a, $claves, true);
                    if ($idx !== false) {
                        $encontradas[$campo] = (int) $idx;
                        break;
                    }
                }
            }
            if (isset($encontradas['pais'], $encontradas['nombre'])) {
                $faltan = array_diff($requeridos, array_keys($encontradas));
                if ($faltan) {
                    throw new UnexpectedValueException("$pestania fila $nroFila: faltan columnas " . implode(', ', $faltan));
                }
                $columnas = $encontradas;
            }
            continue;
        }

        $celda = static fn (string $campo): string => isset($columnas[$campo]) ? ($campos[$columnas[$campo]] ?? '') : '';
        $nombre = $celda('nombre');
        if ($nombre === '') {
            continue; // fila vacía
        }
        $donde = "$pestania fila $nroFila ($nombre)";

        // Categoría del sitio (y subgrupo, para las vitaminas).
        $valorCategoria = $celda('categoria');
        if (!array_key_exists(clave($valorCategoria), $config['categoria'])) {
            throw new UnexpectedValueException("$donde: categoría desconocida «{$valorCategoria}»");
        }
        $categoria = $config['categoria'][clave($valorCategoria)];
        if ($categoria === null) {
            $omitidas[] = "$donde: categoría «{$valorCategoria}» (sale de la pestaña APIS TODOS)";
            continue;
        }
        $subgrupo = $celda('grupo');
        if ($categoria === '*vitamina') {
            $categoria = 'Vitaminas';
            $subgrupo = canonico($valorCategoria, TIPOS_VITAMINA) ?? $valorCategoria;
        }

        // Área(s): fijas por pestaña, o según la columna "Uso".
        if ($config['areas'] === null) {
            $area = USO_A_AREA[clave($celda('uso'))] ?? null;
            if ($area === null) {
                throw new UnexpectedValueException("$donde: uso desconocido «{$celda('uso')}»");
            }
            $areas = [$area];
        } else {
            $areas = $config['areas'];
        }

        try {
            $paises = parsear_paises($celda('pais'));
        } catch (UnexpectedValueException $e) {
            throw new UnexpectedValueException("$donde: " . $e->getMessage());
        }

        $representada = $celda('representada');
        if (clave($representada) === 'otros') {
            $representada = ''; // se muestra bajo "Otros", igual que las vacías
        }

        foreach ($areas as $area) {
            if (!in_array($categoria, CATEGORIAS_POR_AREA[$area], true)) {
                throw new UnexpectedValueException("$donde: la categoría «{$categoria}» no existe en $area");
            }
            $fichas[] = [
                'nombre'           => $nombre,
                'nombre_comercial' => $celda('nombre_comercial'),
                'representada'     => $representada,
                'area_negocio'     => $area,
                'categoria'        => $categoria,
                'subgrupo'         => $subgrupo,
                'descripcion'      => descripcion_html($celda('descripcion')),
                'paises'           => $paises,
                'pestania'         => $pestania,
                'donde'            => $donde,
            ];
        }
    }
    fclose($fh);

    if ($columnas === null) {
        throw new UnexpectedValueException("$pestania: no se encontró la fila de encabezado (País / Nombre)");
    }
    return ['fichas' => $fichas, 'omitidas' => $omitidas];
}

/* -------------------------------------------------------------- proceso */

$dir = arg_valor($argv, 'dir');
$dryRun = arg_flag($argv, 'dry-run');
$force = arg_flag($argv, 'force');
$estadoNuevas = arg_valor($argv, 'estado', 'published');

if ($dir === null || !is_dir($dir)) {
    fwrite(STDERR, "Uso: php data/import_pharma.php --dir=CARPETA [--dry-run] [--force] [--estado=published|draft]\n");
    exit(1);
}
if (!in_array($estadoNuevas, ESTADOS, true)) {
    fwrite(STDERR, "--estado debe ser published o draft\n");
    exit(1);
}

// --- 1. Lectura y validación completa ANTES de tocar la base --------------
$fichas = [];
$omitidas = [];
$avisos = [];
$leidas = [];
try {
    foreach (glob(rtrim($dir, '/') . '/*.csv') ?: [] as $ruta) {
        $pestania = pestania_de($ruta);
        if (!isset(PESTANIAS[$pestania])) {
            $avisos[] = 'archivo ignorado (pestaña desconocida): ' . basename($ruta);
            continue;
        }
        $r = leer_pestania($ruta, $pestania, PESTANIAS[$pestania]);
        $leidas[$pestania] = count($r['fichas']);
        $fichas = array_merge($fichas, $r['fichas']);
        $omitidas = array_merge($omitidas, $r['omitidas']);
    }
} catch (UnexpectedValueException $e) {
    fwrite(STDERR, "ERROR, no se importó nada: " . $e->getMessage() . "\n");
    exit(1);
}
if (!$leidas) {
    fwrite(STDERR, "No se encontró ningún CSV de pestaña conocida en $dir\n");
    exit(1);
}

// La misma ficha dos veces en la planilla: se usa la primera y se avisa.
$porClave = [];
foreach ($fichas as $f) {
    $k = implode("\0", [$f['nombre'], $f['nombre_comercial'], $f['representada'], $f['area_negocio']]);
    if (isset($porClave[$k])) {
        $avisos[] = "repetida: {$f['donde']} (ya estaba en {$porClave[$k]['donde']}), se ignora";
        continue;
    }
    $porClave[$k] = $f;
}
$fichas = array_values($porClave);

// --- 2. Escritura ----------------------------------------------------------
// La migración de esquema corre siempre (es idempotente): el importador
// necesita las columnas y la tabla de países.
if (!$dryRun) {
    foreach (migrar(db()) as $l) {
        echo "[migración] $l\n";
    }
}

$pdo = db();
$selUno = $pdo->prepare(
    'SELECT * FROM productos
     WHERE nombre = ? AND nombre_comercial = ? AND representada = ? AND area_negocio = ? LIMIT 1'
);
$insert = $pdo->prepare(
    'INSERT INTO productos
       (nombre, nombre_comercial, representada, area_negocio, categoria, subgrupo,
        descripcion, estado, origen, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$selPaises = $pdo->prepare('SELECT pais FROM producto_paises WHERE producto_id = ? ORDER BY pais');
$delPaises = $pdo->prepare('DELETE FROM producto_paises WHERE producto_id = ?');
$insPais = $pdo->prepare('INSERT OR IGNORE INTO producto_paises (producto_id, pais) VALUES (?, ?)');

$nuevas = 0;
$actualizadas = 0;
$sinCambios = 0;
$porArea = [];
$detalle = [];

if (!$dryRun) {
    $pdo->beginTransaction();
}
try {
    foreach ($fichas as $f) {
        $porArea[$f['area_negocio']][$f['categoria']] = ($porArea[$f['area_negocio']][$f['categoria']] ?? 0) + 1;

        // Si la base todavía no está migrada (dry-run sobre una base vieja),
        // no hay cómo buscar: todas cuentan como nuevas.
        try {
            $selUno->execute([$f['nombre'], $f['nombre_comercial'], $f['representada'], $f['area_negocio']]);
            $existente = $selUno->fetch();
        } catch (PDOException $e) {
            $existente = false;
        }

        if (!$existente) {
            if (!$dryRun) {
                $insert->execute([
                    $f['nombre'], $f['nombre_comercial'], $f['representada'], $f['area_negocio'],
                    $f['categoria'], $f['subgrupo'], $f['descripcion'], $estadoNuevas,
                    'import:pharma:' . $f['pestania'], now_sql(),
                ]);
                $id = (int) $pdo->lastInsertId();
                foreach ($f['paises'] as $p) {
                    $insPais->execute([$id, $p]);
                }
            }
            $nuevas++;
            continue;
        }

        // Ficha existente: sin --force sólo se completa lo vacío y se suman
        // países; con --force la planilla pisa lo que haya.
        $id = (int) $existente['id'];
        $cambios = [];
        foreach (['descripcion', 'categoria', 'subgrupo'] as $campo) {
            $actual = (string) $existente[$campo];
            if ($f[$campo] !== $actual && ($force || trim($actual) === '')) {
                $cambios[$campo] = $f[$campo];
            }
        }

        $selPaises->execute([$id]);
        $paisesActuales = $selPaises->fetchAll(PDO::FETCH_COLUMN);
        $paisesNuevos = $force ? $f['paises'] : array_values(array_unique(array_merge($paisesActuales, $f['paises'])));
        sort($paisesNuevos);
        $cambianPaises = $paisesNuevos !== $paisesActuales;

        if (!$cambios && !$cambianPaises) {
            $sinCambios++;
            continue;
        }

        if (!$dryRun) {
            if ($cambios) {
                $set = implode(', ', array_map(static fn ($c) => "$c = ?", array_keys($cambios)));
                $pdo->prepare("UPDATE productos SET $set, updated_at = ? WHERE id = ?")
                    ->execute([...array_values($cambios), now_sql(), $id]);
            }
            if ($cambianPaises) {
                $delPaises->execute([$id]);
                foreach ($paisesNuevos as $p) {
                    $insPais->execute([$id, $p]);
                }
            }
        }
        $actualizadas++;
        $que = array_keys($cambios);
        if ($cambianPaises) {
            $que[] = 'países: ' . implode(', ', $paisesActuales ?: ['—']) . ' → ' . implode(', ', $paisesNuevos);
        }
        $detalle[] = sprintf('  ~ %s [%s] %s', $f['nombre'], $f['area_negocio'], implode('; ', $que));
    }

    if (!$dryRun) {
        $pdo->commit();
    }
} catch (Throwable $e) {
    if (!$dryRun && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "ERROR, no se importó nada: " . $e->getMessage() . "\n");
    exit(1);
}

/* ----------------------------------------------------------------- reporte */

$modo = $dryRun ? 'SIMULACIÓN (--dry-run, no se escribió nada)' : 'IMPORTACIÓN APLICADA';
echo "\n== $modo ==\n";
echo "Carpeta: $dir\n\n";

echo "Fichas por pestaña (una por área):\n";
foreach ($leidas as $pestania => $n) {
    printf("  %-22s %4d\n", $pestania, $n);
}

echo "\nFichas por área y categoría:\n";
foreach (AREAS_PHARMA as $area) {
    if (empty($porArea[$area])) {
        continue;
    }
    printf("  %-12s %4d\n", $area, array_sum($porArea[$area]));
    foreach (CATEGORIAS_POR_AREA[$area] as $cat) {
        if (!empty($porArea[$area][$cat])) {
            printf("      %-22s %4d\n", $cat, $porArea[$area][$cat]);
        }
    }
}

printf("\nTotal: %d fichas | nuevas: %d | actualizadas: %d | sin cambios: %d | filas omitidas: %d\n",
    count($fichas), $nuevas, $actualizadas, $sinCambios, count($omitidas));

if ($detalle) {
    echo "\n-- Actualizadas --\n" . implode("\n", $detalle) . "\n";
}
if ($omitidas) {
    echo "\n-- Filas omitidas --\n";
    foreach ($omitidas as $o) {
        echo "  - $o\n";
    }
}
if ($avisos) {
    echo "\n-- Avisos --\n";
    foreach ($avisos as $a) {
        echo "  ! $a\n";
    }
}
echo "\n";
