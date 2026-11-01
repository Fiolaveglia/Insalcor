<?php
/**
 * Shared bootstrap for the PUBLIC (server-rendered) pages.
 *
 * Responsibilities:
 *  - Database access + shared constants (reuses api/bootstrap.php).
 *  - Server-side i18n: pick the language from ?lang / cookie, load the
 *    matching assets/i18n/{lang}.json dictionary, translate chrome via an
 *    output-buffer pass over the finished HTML (no client JS needed).
 *  - Query helpers + card renderers so pages can print DB content directly.
 *
 * Include this at the very top of every public .php page, before any output.
 */
declare(strict_types=1);

require_once __DIR__ . '/../api/bootstrap.php';

/* ------------------------------------------------------------------ i18n */

const I18N_SUPPORTED = ['es', 'en'];
const I18N_COOKIE = 'insalcor_lang';

function current_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    $candidate = null;
    if (isset($_GET['lang']) && in_array($_GET['lang'], I18N_SUPPORTED, true)) {
        $candidate = $_GET['lang'];
        // Persist the choice so it carries across pages (incl. static .html).
        if (!headers_sent()) {
            setcookie(I18N_COOKIE, $candidate, [
                'expires' => time() + 60 * 60 * 24 * 365,
                'path' => '/',
                'samesite' => 'Lax',
            ]);
        }
        $_COOKIE[I18N_COOKIE] = $candidate;
    } elseif (isset($_COOKIE[I18N_COOKIE]) && in_array($_COOKIE[I18N_COOKIE], I18N_SUPPORTED, true)) {
        $candidate = $_COOKIE[I18N_COOKIE];
    }

    $lang = $candidate ?: 'es';
    return $lang;
}

/**
 * Resuelve un campo traducible de una fila de la base (productos, noticias,
 * tutoriales): si el idioma actual es inglés y existe `{$campo}_en` con
 * contenido, la devuelve; si no, cae al campo en español (siempre presente).
 * Así el contenido sin traducir sigue mostrándose (en español) en vez de
 * quedar vacío.
 */
function campo_i18n(array $row, string $campo): string
{
    if (current_lang() === 'en') {
        $en = trim((string) ($row[$campo . '_en'] ?? ''));
        if ($en !== '') {
            return $en;
        }
    }
    return (string) ($row[$campo] ?? '');
}

/**
 * Valor de AREAS al que corresponde $area, ignorando acentos y mayúsculas.
 * Devuelve null si no es un área conocida: `noticias.categoria` es texto libre
 * y todavía hay filas viejas ("Comunidad", "Institucional") anteriores a que
 * la API validara el campo contra AREAS.
 */
function area_canonica(string $area): ?string
{
    $buscada = normalizar_texto($area);
    foreach (AREAS as $canonica) {
        if (normalizar_texto($canonica) === $buscada) {
            return $canonica;
        }
    }
    return null;
}

/**
 * Etiqueta de un área de negocio para mostrar en pantalla, en el idioma
 * activo. El valor guardado en la base nunca cambia; sólo cambia lo que ve
 * el usuario. Un valor que no es un área (una categoría vieja de noticia) se
 * devuelve tal cual, sin inventarle traducción.
 */
function area_label(string $area): string
{
    $canonica = area_canonica($area);
    if ($canonica === null) {
        return $area;
    }
    $mapa = current_lang() === 'en' ? AREAS_EN : AREAS_ES;
    return $mapa[$canonica] ?? $canonica;
}

/**
 * Etiqueta de una especie para mostrar en pantalla. Igual que con las áreas,
 * el valor guardado (el de ESPECIES) es siempre el mismo.
 */
function especie_label(string $especie): string
{
    if (current_lang() === 'en') {
        return ESPECIES_EN[$especie] ?? $especie;
    }
    return $especie;
}

/**
 * Opciones [valor => etiqueta] para el filtro de "Categorías" (especies).
 * El valor SIEMPRE es el de ESPECIES (el que se guarda en la base y se usa
 * en el filtro ?especie=...); sólo la etiqueta que ve el usuario cambia
 * según el idioma activo.
 */
function especie_options(): array
{
    $out = [];
    foreach (ESPECIES as $especie) {
        $out[$especie] = especie_label($especie);
    }
    // Orden alfabético por el nombre que se muestra (respeta tildes: Ganadería, Lechería)
    if (class_exists('Collator')) {
        $collator = new Collator(current_lang() === 'en' ? 'en' : 'es');
        uasort($out, [$collator, 'compare']);
    } else {
        uasort($out, 'strcasecmp');
    }
    return $out;
}

function i18n_dict(): array
{
    static $dict = null;
    if ($dict !== null) {
        return $dict;
    }
    $file = root_path() . '/assets/i18n/' . current_lang() . '.json';
    $dict = [];
    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $dict = $decoded;
        }
    }
    return $dict;
}

/** Translate a chrome key. Returns the raw dictionary string (trusted JSON). */
function t(string $key, array $vars = []): string
{
    $dict = i18n_dict();
    $value = array_key_exists($key, $dict) ? (string) $dict[$key] : $key;
    foreach ($vars as $k => $v) {
        $value = str_replace('{' . $k . '}', (string) $v, $value);
    }
    return $value;
}

/** Escape helper for interpolated DB values. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Output-buffer callback: translate every [data-i18n*] element/attribute in
 * the finished page using the active dictionary. Elements carrying data-i18n
 * are simple leaf nodes in these templates, so a targeted regex is safe.
 */
function i18n_translate_html(string $html): string
{
    $dict = i18n_dict();

    // Text content: <tag ... data-i18n="key" ...>OLD</tag>
    $html = preg_replace_callback(
        '/(<(\w+)\b[^>]*\bdata-i18n="([^"]+)"[^>]*>)([^<]*)(<\/\2>)/',
        static function (array $m) use ($dict): string {
            $key = $m[3];
            $text = array_key_exists($key, $dict) ? htmlspecialchars((string) $dict[$key], ENT_QUOTES, 'UTF-8') : $m[4];
            return $m[1] . $text . $m[5];
        },
        $html
    ) ?? $html;

    // placeholder attribute
    $html = preg_replace_callback(
        '/<(\w+)\b([^>]*\bdata-i18n-placeholder="([^"]+)"[^>]*)>/',
        static function (array $m) use ($dict): string {
            $key = $m[3];
            if (!array_key_exists($key, $dict)) {
                return $m[0];
            }
            $val = htmlspecialchars((string) $dict[$key], ENT_QUOTES, 'UTF-8');
            $attrs = $m[2];
            if (preg_match('/\splaceholder="[^"]*"/', $attrs)) {
                $attrs = preg_replace('/\splaceholder="[^"]*"/', ' placeholder="' . $val . '"', $attrs, 1);
            } else {
                $attrs .= ' placeholder="' . $val . '"';
            }
            return '<' . $m[1] . $attrs . '>';
        },
        $html
    ) ?? $html;

    // aria-label attribute
    $html = preg_replace_callback(
        '/<(\w+)\b([^>]*\bdata-i18n-aria="([^"]+)"[^>]*)>/',
        static function (array $m) use ($dict): string {
            $key = $m[3];
            if (!array_key_exists($key, $dict)) {
                return $m[0];
            }
            $val = htmlspecialchars((string) $dict[$key], ENT_QUOTES, 'UTF-8');
            $attrs = $m[2];
            if (preg_match('/\saria-label="[^"]*"/', $attrs)) {
                $attrs = preg_replace('/\saria-label="[^"]*"/', ' aria-label="' . $val . '"', $attrs, 1);
            } else {
                $attrs .= ' aria-label="' . $val . '"';
            }
            return '<' . $m[1] . $attrs . '>';
        },
        $html
    ) ?? $html;

    // Selected-language flag in the switcher (only the ".selected" display).
    $flag = current_lang() === 'en' ? 'en.png' : 'uy.png';
    $html = preg_replace(
        '/(class="selected"><img src="[^"]*module-language\/)[^"]*(")/',
        '${1}' . $flag . '${2}',
        $html
    ) ?? $html;

    return $html;
}

/** Start buffering so the whole page can be translated on flush. */
function i18n_begin(): void
{
    current_lang(); // resolves + sets cookie before any output
    ob_start('i18n_translate_html');
}

/* ------------------------------------------------------------ asset URLs */

function asset(?string $path): string
{
    if (!$path) {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return ltrim($path, '/');
}

/* ------------------------------------------------------------ date parts */

const I18N_MONTHS = [
    'es' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
    'en' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
];

function date_parts(?string $iso, ?string $lang = null): array
{
    $lang = $lang ?: current_lang();
    $months = I18N_MONTHS[$lang] ?? I18N_MONTHS['es'];
    $ts = $iso ? strtotime($iso . ' UTC') : false;
    if ($ts === false) {
        return ['day' => '', 'month' => '', 'year' => ''];
    }
    return [
        'day' => (string) (int) gmdate('j', $ts),
        'month' => $months[(int) gmdate('n', $ts) - 1],
        'year' => gmdate('Y', $ts),
    ];
}

/* --------------------------------------------------------- data queries */

/**
 * Published products for an area, with optional sidebar filters.
 * $filters keys: q, especie (catálogo único: mismo campo para las 3 áreas).
 */
function pub_productos(string $area, array $filters = []): array
{
    $sql = "SELECT p.* FROM productos p WHERE p.estado = 'published' AND p.area_negocio = ?";
    $params = [$area];

    // Un producto puede estar en varias especies: alcanza con que exista el vínculo.
    if (!empty($filters['especie'])) {
        $sql .= ' AND EXISTS (SELECT 1 FROM producto_especies pe
                              WHERE pe.producto_id = p.id AND pe.especie = ?)';
        $params[] = $filters['especie'];
    }
    if (!empty($filters['q'])) {
        $sql .= ' AND (p.nombre LIKE ?
                       OR EXISTS (SELECT 1 FROM producto_especies pe
                                  WHERE pe.producto_id = p.id AND pe.especie LIKE ?))';
        $like = '%' . $filters['q'] . '%';
        array_push($params, $like, $like);
    }

    $sql .= ' ORDER BY p.updated_at DESC, p.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function pub_producto(int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM productos WHERE id = ? AND estado = 'published'");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['especies'] = producto_especies($id);
    return $row;
}

/** @return string[] Especies de un producto, ordenadas. */
function producto_especies(int $productoId): array
{
    $stmt = db()->prepare('SELECT especie FROM producto_especies WHERE producto_id = ? ORDER BY especie');
    $stmt->execute([$productoId]);
    return array_column($stmt->fetchAll(), 'especie');
}

function pub_noticias(array $filters = []): array
{
    $sql = "SELECT * FROM noticias WHERE estado = 'published'";
    $params = [];
    if (!empty($filters['q'])) {
        $sql .= ' AND (titulo LIKE ? OR extracto LIKE ? OR categoria LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        array_push($params, $like, $like, $like);
    }
    $sql .= ' ORDER BY COALESCE(published_at, created_at) DESC, id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function pub_noticia(int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM noticias WHERE id = ? AND estado = 'published'");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function pub_tutoriales(int $limit = 0): array
{
    $sql = "SELECT * FROM tutoriales WHERE estado = 'published' ORDER BY COALESCE(published_at, created_at) DESC, id DESC";
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }
    return db()->query($sql)->fetchAll();
}

function pub_tutorial(int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM tutoriales WHERE id = ? AND estado = 'published'");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/* ------------------------------------------------- buscador del header */

/**
 * Mínimo de caracteres del buscador del header. Debajo de esto no se busca
 * (una consulta de 1 letra devolvería medio catálogo y no le sirve a nadie).
 */
const BUSCADOR_MIN_CHARS = 2;

/**
 * Término tipeado en el buscador, ya limpio. Devuelve '' cuando no alcanza
 * el mínimo: el llamador lo trata como "todavía no hay búsqueda".
 */
function termino_busqueda(string $param = 'q'): string
{
    $q = trim((string) ($_GET[$param] ?? ''));
    // Colapsa espacios repetidos para que "sal   mineral" siga matcheando.
    $q = (string) preg_replace('/\s+/u', ' ', $q);
    return mb_strlen($q) >= BUSCADOR_MIN_CHARS ? $q : '';
}

/**
 * Acentos que se ignoran al comparar textos: "Nutrición" tiene que
 * encontrarse tipeando "nutricion", y "Nutrición Animal" guardado en una
 * noticia vieja tiene que resolver al área "Nutricion Animal". Se listan las
 * dos cajas porque el lower() de SQLite sólo baja ASCII (a 'Á' no le hace nada).
 */
const TEXTO_ACENTOS = [
    'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
    'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
    'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
    'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
    'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
    'ñ' => 'n', 'ç' => 'c',
    'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a',
    'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
    'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
    'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o',
    'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
    'Ñ' => 'n', 'Ç' => 'c',
];

/** Minúsculas y sin acentos: la forma canónica para comparar dos textos. */
function normalizar_texto(?string $texto): string
{
    return strtr(mb_strtolower(trim((string) $texto), 'UTF-8'), TEXTO_ACENTOS);
}

/**
 * Expresión SQL que normaliza una columna igual que normalizar_texto().
 * Se arma con lower() + replace() anidados en vez de registrar una función
 * SQL propia porque PDO::sqliteCreateFunction() está deprecada desde PHP 8.5.
 * Los literales son constantes del código, nunca entrada del usuario.
 */
function sql_normalizado(string $columna): string
{
    $expr = 'lower(' . $columna . ')';
    foreach (TEXTO_ACENTOS as $con => $sin) {
        $expr = "replace($expr, '$con', '$sin')";
    }
    return $expr;
}

/**
 * Patrón LIKE del término. Escapa con '!' los comodines de SQL para que un
 * % o un _ tipeado por el usuario se busque como carácter literal.
 */
function like_busqueda(string $q): string
{
    $normalizado = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], normalizar_texto($q));
    return '%' . $normalizado . '%';
}

/**
 * Campos en los que mira el buscador, del más relevante al menos: primero el
 * nombre/título, después el resumen, y al final el cuerpo del artículo. El
 * orden de esta lista ES el orden de relevancia de los resultados.
 *
 * El segundo valor marca los campos que guardan HTML (los del editor): esos
 * se verifican aparte sobre el texto sin etiquetas, así buscar "br" o "href"
 * no devuelve todos los artículos.
 */
const BUSCADOR_CAMPOS_PRODUCTOS = [
    ['nombre', false],
    ['nombre_en', false],
    ['nombre_comercial', false],
    ['representada', false],
    ['descripcion', true],
    ['descripcion_en', true],
];

const BUSCADOR_CAMPOS_NOTICIAS = [
    ['titulo', false],
    ['titulo_en', false],
    ['extracto', false],
    ['extracto_en', false],
    ['contenido', true],
    ['contenido_en', true],
];

/**
 * Corre la búsqueda sobre una tabla: arma el OR de todos los campos, y
 * después descarta y ordena en PHP con ordenar_por_relevancia().
 *
 * @param array<int, array{0: string, 1: bool}> $campos
 * @return array<int, array<string, mixed>>
 */
function buscar_en(string $tabla, array $campos, string $q, string $orden): array
{
    $condiciones = [];
    $params = [];
    $like = like_busqueda($q);
    foreach ($campos as [$campo, $_esHtml]) {
        $condiciones[] = sql_normalizado($campo) . " LIKE ? ESCAPE '!'";
        $params[] = $like;
    }

    $sql = sprintf(
        "SELECT * FROM %s WHERE estado = 'published' AND (%s) ORDER BY %s",
        $tabla,
        implode(' OR ', $condiciones),
        $orden
    );
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return ordenar_por_relevancia($stmt->fetchAll(), $campos, $q);
}

/**
 * Ordena las filas por el campo en el que coincidió el término (un producto
 * que se llama así va antes que uno que sólo lo menciona en la descripción),
 * manteniendo el orden que traía la consulta entre las de igual relevancia.
 *
 * De paso descarta las filas cuya única coincidencia estaba dentro del HTML
 * y no en el texto visible.
 *
 * @param array<int, array<string, mixed>> $filas
 * @param array<int, array{0: string, 1: bool}> $campos
 * @return array<int, array<string, mixed>>
 */
function ordenar_por_relevancia(array $filas, array $campos, string $q): array
{
    $termino = normalizar_texto($q);
    $conRelevancia = [];

    foreach ($filas as $posicion => $fila) {
        $relevancia = null;
        foreach ($campos as $nivel => [$campo, $esHtml]) {
            $valor = (string) ($fila[$campo] ?? '');
            if ($valor === '') {
                continue;
            }
            if ($esHtml) {
                $valor = strip_tags(html_entity_decode($valor, ENT_QUOTES, 'UTF-8'));
            }
            if (str_contains(normalizar_texto($valor), $termino)) {
                $relevancia = $nivel;
                break;
            }
        }
        // Sólo coincidía en el marcado, no en lo que el usuario lee.
        if ($relevancia === null) {
            continue;
        }
        $conRelevancia[] = ['relevancia' => $relevancia, 'posicion' => $posicion, 'fila' => $fila];
    }

    usort(
        $conRelevancia,
        static fn(array $a, array $b): int =>
            [$a['relevancia'], $a['posicion']] <=> [$b['relevancia'], $b['posicion']]
    );

    return array_column($conRelevancia, 'fila');
}

/**
 * Productos publicados que coinciden con el término, por nombre o
 * descripción, en cualquiera de los dos idiomas. Se busca siempre en todos
 * los campos, sin importar el idioma en el que esté navegando el usuario.
 *
 * @return array<int, array<string, mixed>>
 */
function buscar_productos(string $q): array
{
    if ($q === '') {
        return [];
    }
    return buscar_en('productos', BUSCADOR_CAMPOS_PRODUCTOS, $q, 'nombre COLLATE NOCASE, id');
}

/**
 * Noticias publicadas que coinciden con el término, por título, extracto o
 * contenido, en cualquiera de los dos idiomas.
 *
 * @return array<int, array<string, mixed>>
 */
function buscar_noticias(string $q): array
{
    if ($q === '') {
        return [];
    }
    return buscar_en('noticias', BUSCADOR_CAMPOS_NOTICIAS, $q, 'COALESCE(published_at, created_at) DESC, id DESC');
}

/* ------------------------------------------------- sidebar filter helpers */

/** Read the active sidebar filters from the query string. */
function active_filters(): array
{
    return [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'especie' => trim((string) ($_GET['especie'] ?? '')),
    ];
}

/**
 * Build a URL that toggles one filter on/off while preserving the others
 * (and the current language / search query).
 */
function filter_url(string $type, string $value, string $anchor = ''): string
{
    $params = array_filter([
        'lang' => $_GET['lang'] ?? null,
        'q' => $_GET['q'] ?? null,
        'especie' => $_GET['especie'] ?? null,
    ], static fn ($v) => $v !== null && $v !== '');

    if (($params[$type] ?? null) === $value) {
        unset($params[$type]); // clicking the active filter clears it
    } else {
        $params[$type] = $value;
    }

    $qs = http_build_query($params);
    $base = $qs ? '?' . $qs : strtok($_SERVER['REQUEST_URI'], '?');
    return $anchor !== '' ? $base . '#' . $anchor : $base;
}

function filter_active(string $type, string $value): bool
{
    return isset($_GET[$type]) && $_GET[$type] === $value;
}

/** Count published items in an area grouped by a column (unfiltered totals). */
function area_counts(string $area, string $col): array
{
    // `especie` no vive en productos: se cuenta a través de la tabla de vínculos,
    // así un producto suma en cada una de sus especies.
    if ($col === 'especie') {
        $stmt = db()->prepare(
            "SELECT pe.especie AS k, COUNT(*) AS c
             FROM producto_especies pe
             JOIN productos p ON p.id = pe.producto_id
             WHERE p.estado = 'published' AND p.area_negocio = ?
             GROUP BY pe.especie"
        );
    } else {
        $stmt = db()->prepare(
            "SELECT $col AS k, COUNT(*) AS c FROM productos
             WHERE estado = 'published' AND area_negocio = ? AND $col <> '' GROUP BY $col"
        );
    }
    $stmt->execute([$area]);
    $out = [];
    foreach ($stmt->fetchAll() as $r) {
        $out[$r['k']] = (int) $r['c'];
    }
    return $out;
}

/* -------------------------------------------------------- card renderers */

/**
 * A dónde lleva un producto. Los de Nutrición tienen página de detalle; los
 * del catálogo Pharma / VetPharma / Suplementos no: se ven desplegados en
 * pharma-vetpharma.php, en su área y categoría (el JS de esa página abre
 * el desplegable de #producto-ID).
 */
function producto_url(array $item, string $detailBase = 'product-single.php'): string
{
    $area = (string) ($item['area_negocio'] ?? '');
    if (isset(SEGMENTOS_PHARMA[$area])) {
        return 'pharma-vetpharma.php?' . http_build_query(array_filter([
            'seg' => SEGMENTOS_PHARMA[$area],
            'categoria' => (string) ($item['categoria'] ?? ''),
        ], static fn ($v) => $v !== '')) . '#producto-' . (int) $item['id'];
    }
    return $detailBase . '?id=' . (int) $item['id'];
}

function render_product_card(array $item, string $detailBase): string
{
    $img = asset($item['imagen']);
    $href = producto_url($item, $detailBase);
    $nombre = e(campo_i18n($item, 'nombre'));
    // Los del catálogo Pharma no tienen detalle para el modal: navegan a su
    // página, y muestran área · categoría (en el buscador aparecen junto a
    // los de Nutrición y el mismo compuesto puede estar en Pharma y VetPharma).
    $esPharma = isset(SEGMENTOS_PHARMA[$item['area_negocio'] ?? '']);
    $abrir = $esPharma ? '' : ' js-open-product';
    $sub = $esPharma
        ? '<div class="product-category"><span>' . e(implode(' · ', array_filter([
            area_label((string) $item['area_negocio']),
            categoria_label((string) ($item['categoria'] ?? '')),
        ]))) . '</span></div>'
        : '';

    if ($img) {
        return '
      <div class="col-12 col-md-6 col-lg-4" data-product-card>
        <div class="product-item">
          <div class="product-img">
            <img src="' . e($img) . '" alt="' . $nombre . '"/>
            <a class="ver-detalle' . $abrir . '" href="' . e($href) . '"><i class="fas fa-eye"></i> ' . e(t('common.view_details')) . '</a>
            <div class="badge"></div>
          </div>
          <div class="product-content">
            <div class="product-title"><a class="' . trim($abrir) . '" href="' . e($href) . '">' . $nombre . '</a></div>' . $sub . '
          </div>
        </div>
      </div>';
    }

    // Sin imagen cargada: nada de foto genérica. Se mantiene la misma
    // estructura que la card con foto (caja 3:4 arriba, título abajo) para
    // que el nombre quede siempre a la misma altura en toda la grilla; en la
    // caja vacía va el botón de Ver detalle, siempre visible.
    return '
      <div class="col-12 col-md-6 col-lg-4" data-product-card>
        <div class="product-item product-item--no-image">
          <div class="product-img product-img--empty">
            <a class="btn btn--secondary btn-sm' . $abrir . '" href="' . e($href) . '"><i class="fas fa-eye"></i> ' . e(t('common.view_details')) . '</a>
          </div>
          <div class="product-content">
            <div class="product-title"><a class="' . trim($abrir) . '" href="' . e($href) . '">' . $nombre . '</a></div>' . $sub . '
          </div>
        </div>
      </div>';
}

function render_recent_product(array $item, string $detailBase): string
{
    $img = asset($item['imagen']);
    $href = $detailBase . '?id=' . (int) $item['id'];
    $nombre = e(campo_i18n($item, 'nombre'));

    if ($img) {
        return '
        <div class="product">
          <div class="product-img"><img src="' . e($img) . '" alt="' . $nombre . '"/></div>
          <div class="product-desc"><div class="product-title"><a class="js-open-product" href="' . e($href) . '">' . $nombre . '</a></div></div>
        </div>';
    }

    // Sin imagen: sólo el texto, sin la miniatura genérica.
    return '
        <div class="product product--no-image">
          <div class="product-desc"><div class="product-title"><a class="js-open-product" href="' . e($href) . '">' . $nombre . '</a></div></div>
        </div>';
}

/**
 * Render a sidebar filter <ul> for one column (especie: catálogo único, misma
 * columna para las 3 áreas de negocio; se muestra como "Categoría" al usuario).
 * $options maps stored value => display label. Counts come from the area.
 */
function render_filter_list(string $area, string $type, array $options, string $anchor = ''): string
{
    $counts = area_counts($area, $type);
    $html = '<ul class="list-unstyled">';
    foreach ($options as $value => $label) {
        $count = $counts[$value] ?? 0;
        $active = filter_active($type, $value) ? ' class="is-active"' : '';
        $html .= '<li><a' . $active . ' href="' . e(filter_url($type, (string) $value, $anchor)) . '">'
            . e($label) . '</a><span>' . $count . '</span></li>';
    }
    $html .= '</ul>';
    return $html;
}

/* ------------------------------------------------------- pagination helpers */

/** Read & clamp a page number from the query string (1-based, min 1). */
function current_page(string $param = 'page'): int
{
    $p = (int) ($_GET[$param] ?? 1);
    return $p > 0 ? $p : 1;
}

/** Build the href for a given page number, preserving every other query param. */
function page_url(int $page, string $param = 'page'): string
{
    $params = $_GET;
    if ($page <= 1) {
        unset($params[$param]);
    } else {
        $params[$param] = $page;
    }
    $qs = http_build_query($params);
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    return $qs ? $path . '?' . $qs : $path;
}

/**
 * Build the href for the language switcher, preserving every other query
 * param (id, filtros, página, etc.) — así cambiar de idioma nunca te saca
 * del producto/noticia/tutorial ni del filtro que estabas viendo.
 */
function lang_switch_url(string $lang): string
{
    $params = $_GET;
    $params['lang'] = $lang;
    $qs = http_build_query($params);
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    return $qs ? $path . '?' . $qs : $path;
}

/**
 * Render a <ul class="pagination"> block (same markup as the site templates)
 * for $totalItems split into pages of $perPage, currently on $page.
 * Returns '' when everything fits on a single page (no pager needed).
 */
function render_pagination(int $totalItems, int $perPage, int $page, string $param = 'page'): string
{
    $totalPages = (int) ceil($totalItems / max(1, $perPage));
    if ($totalPages <= 1) {
        return '';
    }
    $page = max(1, min($page, $totalPages));

    // Cuántos números de página se muestran a la vez (además de "Siguiente").
    // La ventana se desliza para mantener la página actual visible.
    $ventana = 4;
    $inicio = max(1, min($page - intdiv($ventana - 1, 2), $totalPages - $ventana + 1));
    $fin = min($totalPages, $inicio + $ventana - 1);

    $html = '<div class="row"><div class="col-12 clearfix text--center"><ul class="pagination">';
    for ($i = $inicio; $i <= $fin; $i++) {
        $cls = $i === $page ? ' class="current"' : '';
        $html .= '<li><a' . $cls . ' href="' . e(page_url($i, $param)) . '">' . $i . '</a></li>';
    }
    if ($page < $totalPages) {
        $html .= '<li><a href="' . e(page_url($page + 1, $param)) . '" aria-label="Next"><i class="icon-arrow-right"></i></a></li>';
    }
    $html .= '</ul></div></div>';
    return $html;
}
/**
 * A single noticia formatted as an owl-carousel slide, matching the
 * "Artículos y Novedades Recientes" markup on the area pages.
 */
function render_noticia_slide(array $item, string $detailBase): string
{
    $d = date_parts($item['published_at'] ?: $item['created_at']);
    $img = asset($item['imagen']) ?: asset('assets/images/blog/grid/1.jpg');
    $href = $detailBase . '?id=' . (int) $item['id'];
    $titulo = e(campo_i18n($item, 'titulo'));
    return '
            <div>
              <div class="blog-entry" data-hover="">
                <div class="entry-img">
                  <div class="entry-date">
                    <div class="entry-content"><span class="day">' . e($d['day']) . '</span><span class="month">' . e($d['month']) . '</span><span class="year">' . e($d['year']) . '</span></div>
                  </div>
                   <a href="' . e($href) . '"><img src="' . e($img) . '" alt="' . $titulo . '"/></a>
                </div>
                <div class="entry-content">
                  <div class="entry-meta">
                    <div class="entry-category"><a href="javascript:void(0)">' . e(area_label($item['categoria'])) . '</a></div>
                  </div>
                  <div class="entry-title">
                    <h4><a href="' . e($href) . '">' . $titulo . '</a></h4>
                  </div>
                  <div class="entry-bio">
                    <p>' . e(campo_i18n($item, 'extracto')) . '</p>
                  </div>
                  <div class="entry-more"> <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="' . e($href) . '">
                      <div class="line"> <span> </span></div><span>' . e(t('common.see_more')) . '</span></a></div>
                </div>
              </div>
            </div>';
}

function render_noticia_card(array $item, string $detailBase): string
{
    $d = date_parts($item['published_at'] ?: $item['created_at']);
    $img = asset($item['imagen']) ?: asset('assets/images/blog/grid/1.jpg');
    $href = $detailBase . '?id=' . (int) $item['id'];
    $titulo = e(campo_i18n($item, 'titulo'));
    return '
      <div class="col-12 col-md-6 col-lg-4">
        <div class="blog-entry" data-hover="">
          <div class="entry-img">
            <div class="entry-date">
              <div class="entry-content"><span class="day">' . e($d['day']) . '</span><span class="month">' . e($d['month']) . '</span><span class="year">' . e($d['year']) . '</span></div>
            </div>
            <a href="' . e($href) . '"><img src="' . e($img) . '" alt="' . $titulo . '"/></a>
          </div>
          <div class="entry-content">
            <div class="entry-meta">
              <div class="entry-category"><a href="javascript:void(0)">' . e(area_label($item['categoria'])) . '</a></div>
            </div>
            <div class="entry-title">
              <h4><a href="' . e($href) . '">' . $titulo . '</a></h4>
            </div>
            <div class="entry-bio"><p>' . e(campo_i18n($item, 'extracto')) . '</p></div>
            <div class="entry-more">
              <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="' . e($href) . '">
                <div class="line"><span></span></div><span>' . e(t('common.see_more')) . '</span>
              </a>
            </div>
          </div>
        </div>
      </div>';
}

function render_tutorial_card(array $item, string $detailBase): string
{
    $d = date_parts($item['published_at'] ?: $item['created_at']);
    $img = $item['youtube_id']
        ? 'https://img.youtube.com/vi/' . rawurlencode($item['youtube_id']) . '/hqdefault.jpg'
        : asset('assets/images/blog/grid/1.jpg');
    $href = $detailBase . '?id=' . (int) $item['id'];
    $titulo = e(campo_i18n($item, 'titulo'));
    $resumen = trim((string) strip_tags(campo_i18n($item, 'descripcion')));
    if (mb_strlen($resumen) > 140) {
        $resumen = mb_substr($resumen, 0, 140) . '…';
    }
    return '
      <div class="col-12 col-md-6 col-lg-4">
        <div class="blog-entry" data-hover="">
          <div class="entry-img">
            <div class="entry-date">
              <div class="entry-content"><span class="day">' . e($d['day']) . '</span><span class="month">' . e($d['month']) . '</span><span class="year">' . e($d['year']) . '</span></div>
            </div>
            <a href="' . e($href) . '" style="position:relative;display:block">
              <img src="' . e($img) . '" alt="' . $titulo . '"/>
              <i class="fas fa-play-circle" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:48px;color:#fff;opacity:.9"></i>
            </a>
          </div>
          <div class="entry-content">
            <div class="entry-meta">
              <div class="entry-category"><a href="javascript:void(0)">Tutorial</a></div>
            </div>
            <div class="entry-title">
              <h4><a href="' . e($href) . '">' . $titulo . '</a></h4>
            </div>
            <div class="entry-bio"><p>' . e($resumen) . '</p></div>
            <div class="entry-more">
              <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="' . e($href) . '">
                <div class="line"><span></span></div><span>' . e(t('common.see_more')) . '</span>
              </a>
            </div>
          </div>
        </div>
      </div>';
}

/* ---------------------------- catálogo Pharma / VetPharma / Suplementos */

/**
 * Slug de cada área en la URL (?seg=...) y en el id de su contenedor
 * (#segment-pharma), que es lo que usa el selector de áreas de la página.
 */
const SEGMENTOS_PHARMA = ['Pharma' => 'pharma', 'VetPharma' => 'vetpharma', 'Suplementos' => 'suplementos'];

/**
 * Filtros de la página de Pharma leídos del query string. El país se
 * comparte entre las tres áreas (es la misma persona eligiendo dónde está);
 * la categoría y la búsqueda sólo aplican al área de ?seg, porque las
 * categorías de Suplementos no son las de Pharma.
 *
 * @return array{seg: string, pais: string, categoria: string, q: string}
 */
function pharma_filtros(): array
{
    $pais = trim((string) ($_GET['pais'] ?? ''));
    $seg = trim((string) ($_GET['seg'] ?? ''));
    return [
        'seg' => in_array($seg, SEGMENTOS_PHARMA, true) ? $seg : '',
        'pais' => in_array($pais, PAISES, true) ? $pais : '',
        'categoria' => trim((string) ($_GET['categoria'] ?? '')),
        'q' => trim((string) ($_GET['q'] ?? '')),
    ];
}

/** Los filtros que efectivamente aplican a un área. */
function pharma_filtros_de(string $area, array $f): array
{
    $esSuya = $f['seg'] === SEGMENTOS_PHARMA[$area];
    return [
        'pais' => $f['pais'],
        'categoria' => $esSuya && in_array($f['categoria'], CATEGORIAS_POR_AREA[$area], true) ? $f['categoria'] : '',
        'q' => $esSuya ? $f['q'] : '',
    ];
}

/**
 * URL de la página de Pharma con los filtros de un área cambiados.
 * $cambios: clave => valor ('' o null lo quita). Siempre termina en el ancla
 * del área, así al recargar se abre la misma área.
 */
function pharma_url(string $area, array $f, array $cambios): string
{
    $params = array_filter(array_merge([
        'seg' => SEGMENTOS_PHARMA[$area],
        'pais' => $f['pais'],
        'categoria' => $f['categoria'],
        'q' => $f['q'],
    ], $cambios), static fn ($v) => $v !== null && $v !== '');
    $qs = http_build_query($params);
    return strtok($_SERVER['REQUEST_URI'] ?? '', '?') . ($qs ? '?' . $qs : '') . '#segment-' . SEGMENTOS_PHARMA[$area];
}

/**
 * Productos publicados de un área del catálogo Pharma, con sus países
 * (`paises`, separados por coma), filtrados por país / categoría / texto.
 * El texto se busca en nombre, nombre comercial, representada y subgrupo,
 * sin importar acentos (mismo criterio que el buscador del header).
 */
function pub_productos_pharma(string $area, array $f): array
{
    $sql = "SELECT p.*,
                   (SELECT group_concat(pp.pais, ',') FROM producto_paises pp WHERE pp.producto_id = p.id) AS paises
            FROM productos p
            WHERE p.estado = 'published' AND p.area_negocio = ?";
    $params = [$area];

    if ($f['pais'] !== '') {
        $sql .= ' AND EXISTS (SELECT 1 FROM producto_paises pp WHERE pp.producto_id = p.id AND pp.pais = ?)';
        $params[] = $f['pais'];
    }
    if ($f['categoria'] !== '') {
        $sql .= ' AND p.categoria = ?';
        $params[] = $f['categoria'];
    }
    if ($f['q'] !== '') {
        $campos = ['p.nombre', 'p.nombre_en', 'p.nombre_comercial', 'p.representada', 'p.subgrupo'];
        $sql .= ' AND (' . implode(' OR ', array_map(
            static fn ($c) => sql_normalizado($c) . " LIKE ? ESCAPE '!'",
            $campos
        )) . ')';
        array_push($params, ...array_fill(0, count($campos), like_busqueda($f['q'])));
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Etiqueta de una categoría del catálogo en el idioma activo. */
function categoria_label(string $categoria): string
{
    return current_lang() === 'en' ? (CATEGORIAS_EN[$categoria] ?? $categoria) : $categoria;
}

/** Etiqueta de un subgrupo (grupo terapéutico / tipo de vitamina). */
function subgrupo_label(string $subgrupo): string
{
    return current_lang() === 'en' ? (SUBGRUPOS_EN[$subgrupo] ?? $subgrupo) : $subgrupo;
}

/**
 * Ordena y agrupa productos para mostrarlos:
 *   [categoría => [encabezado => [productos]]]
 * El encabezado sale de CATEGORIAS_AGRUPAR_POR (marca o subgrupo); en las
 * categorías que se listan sin agrupar hay un único grupo con clave ''.
 * Las categorías van en el orden de CATEGORIAS_POR_AREA; los encabezados y
 * los productos, alfabéticos (sin importar acentos), con "Otros" al final.
 */
function agrupar_pharma(string $area, array $items): array
{
    $orden = array_flip(CATEGORIAS_POR_AREA[$area]);
    $porCategoria = [];
    foreach ($items as $it) {
        $campo = CATEGORIAS_AGRUPAR_POR[$it['categoria']] ?? null;
        $grupo = $campo === null ? '' : (trim((string) $it[$campo]) ?: "\u{FFFF}otros");
        $porCategoria[$it['categoria']][$grupo][] = $it;
    }
    uksort($porCategoria, static fn ($a, $b) => ($orden[$a] ?? 99) <=> ($orden[$b] ?? 99));

    $porNombre = static fn ($a, $b) => strcmp(
        normalizar_texto(campo_i18n($a, 'nombre') . ' ' . $a['nombre_comercial']),
        normalizar_texto(campo_i18n($b, 'nombre') . ' ' . $b['nombre_comercial'])
    );
    foreach ($porCategoria as &$grupos) {
        uksort($grupos, static fn ($a, $b) => strcmp(normalizar_texto($a), normalizar_texto($b)));
        foreach ($grupos as &$lista) {
            usort($lista, $porNombre);
        }
        unset($lista);
    }
    unset($grupos);
    return $porCategoria;
}

/** Texto del encabezado de un grupo ("Otros" para los sin marca). */
function grupo_pharma_label(string $grupo): string
{
    return $grupo === "\u{FFFF}otros" ? t('pharma.others_group') : subgrupo_label($grupo);
}

/**
 * Un producto del catálogo como desplegable. Cerrado muestra el nombre del
 * compuesto (lo pide el documento); si ese nombre se repite dentro del mismo
 * grupo, suma el nombre comercial en gris para poder distinguirlos.
 */
function render_pharma_item(array $item, bool $mostrarComercial): string
{
    $nombre = campo_i18n($item, 'nombre');
    $comercial = trim((string) $item['nombre_comercial']);
    $paises = array_filter(explode(',', (string) ($item['paises'] ?? '')));
    // Sólo lo que no figura ya como encabezado en la lista: la categoría y el
    // subgrupo siempre lo son, y la marca también en las categorías que se
    // agrupan por marca (Excipientes, Cápsulas...). En el resto la marca
    // (ej. Jiaherb en Extractos, Balchem en Vitaminas) sí aporta.
    $agrupaPorMarca = (CATEGORIAS_AGRUPAR_POR[$item['categoria']] ?? null) === 'representada';
    $marca = $agrupaPorMarca ? '' : trim((string) $item['representada']);
    // Lo que se muestra en el formulario de consulta. contact.php no lo usa:
    // busca el producto por id (ver inc/consulta.php).
    $etiquetaConsulta = $nombre . ($comercial !== '' && normalizar_texto($comercial) !== normalizar_texto($nombre) ? ' (' . $comercial . ')' : '');

    $html = '<details class="pharma-item" id="producto-' . (int) $item['id'] . '">'
        . '<summary><span class="pharma-item__nombre">' . e($nombre) . '</span>'
        . ($mostrarComercial && $comercial !== '' ? '<span class="pharma-item__extra"> · ' . e($comercial) . '</span>' : '')
        . '</summary><div class="pharma-item__body">';
    if ($comercial !== '' && normalizar_texto($comercial) !== normalizar_texto($nombre)) {
        $html .= '<p class="pharma-item__comercial">' . e($comercial) . '</p>';
    }
    if ($marca !== '') {
        $html .= '<p class="pharma-item__meta">' . e($marca) . '</p>';
    }
    $descripcion = campo_i18n($item, 'descripcion');
    if (trim(strip_tags($descripcion)) !== '') {
        $html .= '<div class="pharma-item__desc">' . $descripcion . '</div>';
    }
    if ($paises) {
        $html .= '<p class="pharma-item__paises"><span>' . e(t('pharma.available_in')) . ':</span> ';
        foreach (PAISES as $p) {
            if (in_array($p, $paises, true)) {
                $html .= '<span class="pharma-chip"><img src="https://flagcdn.com/' . PAISES_ISO[$p] . '.svg" alt="" width="18" height="12"/> ' . e($p) . '</span>';
            }
        }
        $html .= '</p>';
    }
    $html .= '<a class="btn btn--secondary btn-sm pharma-item__consultar js-consultar" href="contact.html"'
        . ' data-producto="' . (int) $item['id'] . '" data-area="' . e((string) $item['area_negocio']) . '"'
        . ' data-nombre="' . e($etiquetaConsulta) . '"><i class="fas fa-envelope"></i> '
        . e(t('pharma.ask_product')) . '</a></div></details>';
    return $html;
}

/** Botones de país (banderas). Click en el país activo lo quita. */
function render_pais_filter(string $area, array $f): string
{
    $html = '<div class="pharma-paises" role="group" aria-label="' . e(t('pharma.country_title')) . '">';
    foreach (PAISES as $p) {
        $activo = $f['pais'] === $p;
        $html .= '<a class="pharma-pais' . ($activo ? ' is-active' : '') . '" aria-pressed="' . ($activo ? 'true' : 'false') . '" href="'
            . e(pharma_url($area, $f, ['pais' => $activo ? '' : $p]))
            . '"><img src="https://flagcdn.com/' . PAISES_ISO[$p] . '.svg" alt="" width="36" height="24"/><span>' . e($p) . '</span></a>';
    }
    return $html . '</div>';
}

/**
 * Lista de categorías del sidebar con su cantidad, contada con el país y la
 * búsqueda activos (así el número coincide con lo que se ve al elegirla).
 * Mismo marcado que render_filter_list().
 */
function render_categoria_filter(string $area, array $f, array $conteos): string
{
    $total = array_sum($conteos);
    $html = '<ul class="list-unstyled">'
        . '<li><a' . ($f['categoria'] === '' ? ' class="is-active"' : '') . ' href="' . e(pharma_url($area, $f, ['categoria' => ''])) . '">'
        . e(t('pharma.all_categories')) . '</a><span>' . $total . '</span></li>';
    foreach (CATEGORIAS_POR_AREA[$area] as $cat) {
        $n = $conteos[$cat] ?? 0;
        $activa = $f['categoria'] === $cat;
        $html .= '<li' . ($n === 0 && !$activa ? ' class="is-empty"' : '') . '><a' . ($activa ? ' class="is-active"' : '') . ' href="'
            . e(pharma_url($area, $f, ['categoria' => $activa ? '' : $cat])) . '">' . e(categoria_label($cat)) . '</a><span>' . $n . '</span></li>';
    }
    return $html . '</ul>';
}

/**
 * Bloque completo de productos de un área del catálogo (banderas, contacto,
 * sidebar con búsqueda y categorías, listado agrupado). La plantilla está en
 * inc/catalogo-pharma.php.
 */
function render_catalogo_pharma(string $area, array $filtrosPagina): string
{
    $f = pharma_filtros_de($area, $filtrosPagina);
    $items = pub_productos_pharma($area, $f);

    // Los conteos del sidebar ignoran la categoría elegida (si no, todas las
    // demás darían 0); si hay una elegida hace falta otra consulta.
    $sinCategoria = $f['categoria'] === '' ? $items : pub_productos_pharma($area, array_merge($f, ['categoria' => '']));
    $conteos = array_count_values(array_column($sinCategoria, 'categoria'));

    $grupos = agrupar_pharma($area, $items);
    $slug = SEGMENTOS_PHARMA[$area];

    ob_start();
    include __DIR__ . '/catalogo-pharma.php';
    return (string) ob_get_clean();
}
