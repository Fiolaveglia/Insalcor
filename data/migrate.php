<?php
/**
 * Migración idempotente de la base existente (data/insalcor.sqlite).
 *
 * schema.sql sólo se ejecuta cuando la base es nueva, así que las bases que ya
 * existen necesitan este script para incorporar las columnas nuevas.
 * Se puede correr todas las veces que haga falta.
 *
 *   php data/migrate.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/bootstrap.php';

/** Columnas agregadas después de la versión inicial del esquema. */
const PRODUCTOS_COLUMNAS_NUEVAS = [
    'nota_blog'          => "TEXT NOT NULL DEFAULT ''",
    'tiene_imagen'       => 'INTEGER',
    'tiene_ficha'        => 'INTEGER',
    'tiene_blog'         => 'INTEGER',
    'producto_terminado' => 'INTEGER',
    'nota_replicar'      => "TEXT NOT NULL DEFAULT ''",
    'origen'             => "TEXT NOT NULL DEFAULT ''",
];

/** DDL de `productos` en su forma actual (una ficha, N especies). */
const DDL_PRODUCTOS = <<<'SQL'
CREATE TABLE productos (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre        TEXT NOT NULL,
  descripcion   TEXT NOT NULL DEFAULT '',
  imagen        TEXT NOT NULL DEFAULT '',
  ficha_tecnica TEXT NOT NULL DEFAULT '',
  nota_blog     TEXT NOT NULL DEFAULT '',
  area_negocio  TEXT NOT NULL
                CHECK (area_negocio IN ('Nutricion Animal', 'Pharma', 'VetPharma')),
  categoria     TEXT NOT NULL DEFAULT '',
  marca         TEXT NOT NULL DEFAULT '',
  estado        TEXT NOT NULL DEFAULT 'draft' CHECK (estado IN ('draft', 'published')),
  tiene_imagen       INTEGER,
  tiene_ficha        INTEGER,
  tiene_blog         INTEGER,
  producto_terminado INTEGER,
  nota_replicar TEXT NOT NULL DEFAULT '',
  origen        TEXT NOT NULL DEFAULT '',
  created_at    TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at    TEXT NOT NULL DEFAULT (datetime('now'))
)
SQL;

const DDL_PRODUCTO_ESPECIES = <<<'SQL'
CREATE TABLE producto_especies (
  producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
  especie     TEXT    NOT NULL
              CHECK (especie IN ('Aves','Porcinos','Ganadería','Mascotas',
                                 'Lechería','Equinos','Ovinos')),
  PRIMARY KEY (producto_id, especie)
)
SQL;

/** @return string[] Nombres de columna de una tabla. */
function tabla_columnas(PDO $pdo, string $tabla): array
{
    $stmt = $pdo->query('PRAGMA table_info(' . $tabla . ')');
    return array_column($stmt->fetchAll(), 'name');
}

function tabla_existe(PDO $pdo, string $tabla): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name = ?");
    $stmt->execute([$tabla]);
    return (bool) $stmt->fetch();
}

/** Primer valor no vacío de una lista (para consolidar columnas de texto). */
function primer_no_vacio(array $valores): string
{
    foreach ($valores as $v) {
        if (trim((string) $v) !== '') {
            return (string) $v;
        }
    }
    return '';
}

/** Primer valor no NULL (para los flags SI/NO). */
function primer_no_null(array $valores): ?int
{
    foreach ($valores as $v) {
        if ($v !== null) {
            return (int) $v;
        }
    }
    return null;
}

/**
 * Colapsa la fila-por-especie en una ficha por producto y mueve las especies a
 * `producto_especies`.
 *
 * La tabla vieja queda como `productos_pre_merge` hasta que se verifique el
 * sitio; borrarla es un paso manual.
 *
 * @return string[] Log de lo que se hizo.
 */
function migrar_especies(PDO $pdo): array
{
    $log = [];
    $tieneColumnaEspecie = in_array('especie', tabla_columnas($pdo, 'productos'), true);

    if (!$tieneColumnaEspecie) {
        if (!tabla_existe($pdo, 'producto_especies')) {
            $pdo->exec(DDL_PRODUCTO_ESPECIES);
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_producto_especies_especie ON producto_especies(especie)');
            $log[] = 'producto_especies: tabla creada (productos ya no tenía columna especie).';
        }
        return $log;
    }

    // --- consolidar en memoria para poder reportar cada decisión -------------
    $filas = $pdo->query('SELECT * FROM productos ORDER BY id')->fetchAll();
    $grupos = [];
    foreach ($filas as $f) {
        $grupos[$f['nombre'] . "\0" . $f['area_negocio']][] = $f;
    }

    $productos = [];
    $especies = [];
    foreach ($grupos as $g) {
        $ganador = $g[0]; // el id más bajo: conserva el registro más antiguo
        $col = static fn (string $c): array => array_column($g, $c);

        // Descripción: gana la más larga (las variantes son reescrituras del
        // mismo texto; ver el reporte al final).
        $descripciones = array_values(array_unique(array_filter($col('descripcion'), static fn ($d) => trim((string) $d) !== '')));
        usort($descripciones, static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        if (count($descripciones) > 1) {
            $log[] = sprintf(
                '  descripción divergente en «%s» (%d variantes): se conserva la más larga (%d caracteres), se descartan %s.',
                $ganador['nombre'], count($descripciones), mb_strlen($descripciones[0]),
                implode(' / ', array_map(static fn ($d) => mb_strlen($d) . ' car.', array_slice($descripciones, 1)))
            );
        }

        $productos[] = [
            'id'                 => (int) $ganador['id'],
            'nombre'             => $ganador['nombre'],
            'descripcion'        => $descripciones[0] ?? '',
            'imagen'             => primer_no_vacio($col('imagen')),
            'ficha_tecnica'      => primer_no_vacio($col('ficha_tecnica')),
            'nota_blog'          => primer_no_vacio($col('nota_blog')),
            'area_negocio'       => $ganador['area_negocio'],
            'categoria'          => primer_no_vacio($col('categoria')),
            'marca'              => primer_no_vacio($col('marca')),
            // Si alguna de las fichas estaba publicada, el producto queda publicado.
            'estado'             => in_array('published', $col('estado'), true) ? 'published' : 'draft',
            'tiene_imagen'       => primer_no_null($col('tiene_imagen')),
            'tiene_ficha'        => primer_no_null($col('tiene_ficha')),
            'tiene_blog'         => primer_no_null($col('tiene_blog')),
            'producto_terminado' => primer_no_null($col('producto_terminado')),
            'nota_replicar'      => primer_no_vacio($col('nota_replicar')),
            'origen'             => primer_no_vacio($col('origen')),
            'created_at'         => min($col('created_at')),
            'updated_at'         => max($col('updated_at')),
        ];

        foreach (array_unique(array_filter($col('especie'), static fn ($e) => trim((string) $e) !== '')) as $esp) {
            $especies[] = [(int) $ganador['id'], $esp];
        }
    }

    // --- reescritura de tablas ---------------------------------------------
    // El orden importa: primero se renombra productos (todavía no existe
    // producto_especies, así que no hay FK que SQLite pueda repuntar sola),
    // después se crea la tabla nueva y recién ahí la de especies.
    $pdo->beginTransaction();
    try {
        if (tabla_existe($pdo, 'producto_especies')) {
            $pdo->exec('DROP TABLE producto_especies'); // corrida parcial previa
        }
        if (tabla_existe($pdo, 'productos_pre_merge')) {
            $pdo->exec('DROP TABLE productos_pre_merge');
        }
        $pdo->exec('ALTER TABLE productos RENAME TO productos_pre_merge');
        // El RENAME se lleva los índices puestos: hay que liberar los nombres
        // antes de recrearlos sobre la tabla nueva.
        foreach (['idx_productos_estado', 'idx_productos_area', 'idx_productos_especie', 'idx_productos_unico'] as $idx) {
            $pdo->exec("DROP INDEX IF EXISTS $idx");
        }
        $pdo->exec(DDL_PRODUCTOS);

        $cols = array_keys($productos[0] ?? ['id' => null]);
        $ins = $pdo->prepare(
            'INSERT INTO productos (' . implode(', ', $cols) . ') VALUES ('
            . implode(', ', array_fill(0, count($cols), '?')) . ')'
        );
        foreach ($productos as $p) {
            $ins->execute(array_values($p));
        }

        $pdo->exec(DDL_PRODUCTO_ESPECIES);
        $insEsp = $pdo->prepare('INSERT INTO producto_especies (producto_id, especie) VALUES (?, ?)');
        foreach ($especies as $e) {
            $insEsp->execute($e);
        }

        $pdo->exec('CREATE INDEX idx_productos_estado ON productos(estado)');
        $pdo->exec('CREATE INDEX idx_productos_area ON productos(area_negocio)');
        $pdo->exec('CREATE UNIQUE INDEX idx_productos_unico ON productos(nombre, area_negocio)');
        $pdo->exec('CREATE INDEX idx_producto_especies_especie ON producto_especies(especie)');

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    array_unshift(
        $log,
        sprintf('productos: %d filas colapsadas en %d fichas; %d vínculos en producto_especies.',
            count($filas), count($productos), count($especies)),
        'productos: la tabla anterior quedó como productos_pre_merge (borrala cuando verifiques el sitio).'
    );

    return $log;
}

/**
 * Aplica las migraciones pendientes.
 *
 * @return string[] Log de lo que se hizo (vacío si ya estaba todo aplicado).
 */
/**
 * `users.email` pasa a llamarse `users.username`: el admin se registra con un
 * nombre de usuario, no con un email. Se conserva el valor, así una cuenta
 * vieja sigue entrando usando su email como nombre de usuario.
 *
 * @return string[] Log de lo que se hizo.
 */
function migrar_usuarios(PDO $pdo): array
{
    $cols = tabla_columnas($pdo, 'users');
    if (in_array('username', $cols, true) || !in_array('email', $cols, true)) {
        return [];
    }
    $pdo->exec('ALTER TABLE users RENAME COLUMN email TO username');

    return ['users: columna email renombrada a username (las cuentas existentes '
        . 'entran con su email como nombre de usuario).'];
}

function migrar(PDO $pdo): array
{
    $log = migrar_usuarios($pdo);
    $existentes = tabla_columnas($pdo, 'productos');

    foreach (PRODUCTOS_COLUMNAS_NUEVAS as $col => $tipo) {
        if (in_array($col, $existentes, true)) {
            continue;
        }
        $pdo->exec("ALTER TABLE productos ADD COLUMN $col $tipo");
        $log[] = "productos: + columna $col ($tipo)";
    }

    // Un producto vive en N especies: la columna `especie` se reemplaza por la
    // tabla producto_especies.
    return array_merge($log, migrar_especies($pdo));
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $log = migrar(db());
    echo $log ? implode(PHP_EOL, $log) . PHP_EOL : "Nada para migrar, la base ya está al día." . PHP_EOL;
}
