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
    'nombre_en'          => "TEXT NOT NULL DEFAULT ''",
    'descripcion_en'     => "TEXT NOT NULL DEFAULT ''",
    // Catálogo Pharma / VetPharma / Suplementos (ver schema.sql).
    'nombre_comercial'   => "TEXT NOT NULL DEFAULT ''",
    'representada'       => "TEXT NOT NULL DEFAULT ''",
    'categoria'          => "TEXT NOT NULL DEFAULT ''",
    'subgrupo'           => "TEXT NOT NULL DEFAULT ''",
];

/** DDL de `productos` en su forma actual (una ficha, N especies, N países). */
const DDL_PRODUCTOS = <<<'SQL'
CREATE TABLE productos (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre        TEXT NOT NULL,
  nombre_en     TEXT NOT NULL DEFAULT '',
  descripcion   TEXT NOT NULL DEFAULT '',
  descripcion_en TEXT NOT NULL DEFAULT '',
  imagen        TEXT NOT NULL DEFAULT '',
  ficha_tecnica TEXT NOT NULL DEFAULT '',
  nota_blog     TEXT NOT NULL DEFAULT '',
  nombre_comercial TEXT NOT NULL DEFAULT '',
  representada  TEXT NOT NULL DEFAULT '',
  categoria     TEXT NOT NULL DEFAULT '',
  subgrupo      TEXT NOT NULL DEFAULT '',
  area_negocio  TEXT NOT NULL
                CHECK (area_negocio IN ('Nutricion Animal', 'Pharma', 'VetPharma', 'Suplementos')),
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

const DDL_PRODUCTO_PAISES = <<<'SQL'
CREATE TABLE producto_paises (
  producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
  pais        TEXT    NOT NULL CHECK (pais IN ('Uruguay','Argentina','Paraguay')),
  PRIMARY KEY (producto_id, pais)
)
SQL;

/**
 * Índices de `productos`. La identidad de una ficha es (nombre, nombre
 * comercial, representada, área): en Nutrición los dos del medio quedan
 * vacíos, así que sigue siendo (nombre, área); en Pharma el compuesto solo
 * se repite (ej. "Grasa dura" con 13 nombres comerciales).
 */
const INDICES_PRODUCTOS = [
    'idx_productos_estado'    => 'CREATE INDEX idx_productos_estado ON productos(estado)',
    'idx_productos_area'      => 'CREATE INDEX idx_productos_area ON productos(area_negocio)',
    'idx_productos_categoria' => 'CREATE INDEX idx_productos_categoria ON productos(area_negocio, categoria)',
    'idx_productos_unico'     => 'CREATE UNIQUE INDEX idx_productos_unico ON productos(nombre, nombre_comercial, representada, area_negocio)',
];

function crear_indices_productos(PDO $pdo): void
{
    foreach (INDICES_PRODUCTOS as $sql) {
        $pdo->exec($sql);
    }
}

/**
 * Reconstruye `productos` con DDL_PRODUCTOS (para cambios que SQLite no
 * permite con ALTER: un CHECK, sacar columnas), copiando $columnas.
 *
 * OJO: producto_especies y producto_paises apuntan a productos con
 * ON DELETE CASCADE. Desde SQLite 3.26, renombrar productos repunta esas FKs
 * a la tabla renombrada, y al borrarla el CASCADE las vacía. Por eso se sigue
 * el procedimiento que documenta SQLite para reconstruir una tabla
 * (https://www.sqlite.org/lang_altertable.html#otheralter): FKs apagadas,
 * tabla nueva con otro nombre, copiar, borrar la vieja y renombrar la NUEVA,
 * así las FKs de las tablas hijas nunca se tocan. El PRAGMA no tiene efecto
 * dentro de una transacción: va antes.
 *
 * @param string[] $columnas Columnas a copiar (deben existir en las dos tablas).
 */
function reconstruir_productos(PDO $pdo, array $columnas): void
{
    $lista = implode(', ', $columnas);

    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->beginTransaction();
    try {
        if (tabla_existe($pdo, 'productos_nueva')) {
            $pdo->exec('DROP TABLE productos_nueva'); // corrida parcial previa
        }
        $ddlNueva = preg_replace('/CREATE TABLE productos\b/', 'CREATE TABLE productos_nueva', DDL_PRODUCTOS, 1);
        $pdo->exec($ddlNueva);
        $pdo->exec("INSERT INTO productos_nueva ($lista) SELECT $lista FROM productos");

        // Borrar la tabla vieja se lleva sus índices, liberando los nombres.
        $pdo->exec('DROP TABLE productos');
        $pdo->exec('ALTER TABLE productos_nueva RENAME TO productos');
        crear_indices_productos($pdo);

        // Con las FKs apagadas SQLite no valida nada: se controla a mano
        // antes de confirmar, y ante cualquier problema se deshace todo.
        $rotas = $pdo->query('PRAGMA foreign_key_check')->fetchAll();
        if ($rotas) {
            throw new RuntimeException('foreign_key_check falló: ' . json_encode($rotas));
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        $pdo->exec('PRAGMA foreign_keys = ON');
        throw $e;
    }
    $pdo->exec('PRAGMA foreign_keys = ON');
}

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
        foreach (array_merge(array_keys(INDICES_PRODUCTOS), ['idx_productos_especie']) as $idx) {
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

        crear_indices_productos($pdo);
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

/**
 * Esquema viejo de Nutrición: `categoria` y `marca` dejan de existir. Todo se
 * clasifica por especie.
 *
 * Se dispara sólo si existe `marca`: las bases viejas tenían siempre las dos
 * columnas juntas, y desde el catálogo Pharma hay una `categoria` NUEVA (la del
 * filtro lateral) que no hay que borrar. Como la reconstrucción usa
 * DDL_PRODUCTOS, la tabla resultante ya trae la `categoria` nueva, vacía.
 *
 * @return string[] Log de lo que se hizo.
 */
function migrar_quitar_categoria_marca(PDO $pdo): array
{
    $existentes = tabla_columnas($pdo, 'productos');
    if (!in_array('marca', $existentes, true)) {
        return [];
    }

    // Sólo las columnas que van a sobrevivir, en el orden en que existan hoy.
    reconstruir_productos($pdo, array_values(array_diff($existentes, ['categoria', 'marca'])));

    return ['productos: columnas viejas categoria y marca eliminadas (Nutrición se clasifica por especie).'];
}

/**
 * Catálogo Pharma / VetPharma / Suplementos:
 *  - `area_negocio` acepta 'Suplementos' (es un CHECK: hay que reconstruir);
 *  - la identidad pasa a (nombre, nombre_comercial, representada, área);
 *  - índice por (área, categoría) para el filtro lateral;
 *  - tabla `producto_paises`.
 * Las columnas nuevas ya las agregó el ADD COLUMN de migrar().
 *
 * @return string[] Log de lo que se hizo.
 */
function migrar_catalogo_pharma(PDO $pdo): array
{
    $log = [];
    $sqlDe = static function (string $nombre) use ($pdo): string {
        $stmt = $pdo->prepare('SELECT sql FROM sqlite_master WHERE name = ?');
        $stmt->execute([$nombre]);
        return (string) $stmt->fetchColumn();
    };

    if (!str_contains($sqlDe('productos'), "'Suplementos'")) {
        reconstruir_productos($pdo, tabla_columnas($pdo, 'productos'));
        $log[] = "productos: area_negocio acepta 'Suplementos'; identidad = (nombre, nombre_comercial, representada, área).";
    } elseif (!str_contains($sqlDe('idx_productos_unico'), 'nombre_comercial')) {
        // El CHECK ya estaba al día: alcanza con rehacer el índice.
        $pdo->exec('DROP INDEX IF EXISTS idx_productos_unico');
        $pdo->exec(INDICES_PRODUCTOS['idx_productos_unico']);
        $log[] = 'productos: identidad = (nombre, nombre_comercial, representada, área).';
    }

    if ($sqlDe('idx_productos_categoria') === '') {
        $pdo->exec(INDICES_PRODUCTOS['idx_productos_categoria']);
        $log[] = 'productos: + índice idx_productos_categoria.';
    }

    if (!tabla_existe($pdo, 'producto_paises')) {
        $pdo->exec(DDL_PRODUCTO_PAISES);
        $pdo->exec('CREATE INDEX idx_producto_paises_pais ON producto_paises(pais)');
        $log[] = 'producto_paises: tabla creada.';
    }

    return $log;
}

/** DDL de `tutoriales` (video de YouTube con título y descripción). */
const DDL_TUTORIALES = <<<'SQL'
CREATE TABLE tutoriales (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo        TEXT NOT NULL,
  titulo_en     TEXT NOT NULL DEFAULT '',
  descripcion   TEXT NOT NULL DEFAULT '',
  descripcion_en TEXT NOT NULL DEFAULT '',
  youtube_url   TEXT NOT NULL DEFAULT '',
  youtube_id    TEXT NOT NULL DEFAULT '',
  estado        TEXT NOT NULL DEFAULT 'draft' CHECK (estado IN ('draft', 'published')),
  autor_id      INTEGER,
  vistas        INTEGER NOT NULL DEFAULT 0,
  created_at    TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at    TEXT NOT NULL DEFAULT (datetime('now')),
  published_at  TEXT,
  FOREIGN KEY (autor_id) REFERENCES users(id) ON DELETE SET NULL
)
SQL;

/** Columnas de traducción (inglés) agregadas después de la versión inicial. */
const NOTICIAS_COLUMNAS_NUEVAS = [
    'titulo_en'    => "TEXT NOT NULL DEFAULT ''",
    'extracto_en'  => "TEXT NOT NULL DEFAULT ''",
    'contenido_en' => "TEXT NOT NULL DEFAULT ''",
];

const TUTORIALES_COLUMNAS_NUEVAS = [
    'titulo_en'      => "TEXT NOT NULL DEFAULT ''",
    'descripcion_en' => "TEXT NOT NULL DEFAULT ''",
];

/**
 * Crea la tabla `tutoriales` si todavía no existe (bases creadas antes de
 * esta funcionalidad).
 *
 * @return string[] Log de lo que se hizo.
 */
function migrar_tutoriales(PDO $pdo): array
{
    if (tabla_existe($pdo, 'tutoriales')) {
        return [];
    }
    $pdo->exec(DDL_TUTORIALES);
    $pdo->exec('CREATE INDEX idx_tutoriales_estado ON tutoriales(estado)');

    return ['tutoriales: tabla creada.'];
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
    $log = array_merge($log, migrar_especies($pdo));

    // Esquema viejo de Nutrición: se eliminan categoria/marca.
    $log = array_merge($log, migrar_quitar_categoria_marca($pdo));

    // Catálogo Pharma / VetPharma / Suplementos (área nueva, identidad, países).
    $log = array_merge($log, migrar_catalogo_pharma($pdo));

    // Sección de tutoriales (video de YouTube).
    $log = array_merge($log, migrar_tutoriales($pdo));

    // Traducción al inglés de noticias.
    $existentesNoticias = tabla_columnas($pdo, 'noticias');
    foreach (NOTICIAS_COLUMNAS_NUEVAS as $col => $tipo) {
        if (in_array($col, $existentesNoticias, true)) {
            continue;
        }
        $pdo->exec("ALTER TABLE noticias ADD COLUMN $col $tipo");
        $log[] = "noticias: + columna $col ($tipo)";
    }

    // Traducción al inglés de tutoriales (la tabla ya existe a esta altura,
    // migrar_tutoriales() la crea si hacía falta).
    $existentesTutoriales = tabla_columnas($pdo, 'tutoriales');
    foreach (TUTORIALES_COLUMNAS_NUEVAS as $col => $tipo) {
        if (in_array($col, $existentesTutoriales, true)) {
            continue;
        }
        $pdo->exec("ALTER TABLE tutoriales ADD COLUMN $col $tipo");
        $log[] = "tutoriales: + columna $col ($tipo)";
    }

    return $log;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $log = migrar(db());
    echo $log ? implode(PHP_EOL, $log) . PHP_EOL : "Nada para migrar, la base ya está al día." . PHP_EOL;
}
