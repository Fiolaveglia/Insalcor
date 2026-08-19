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
 * $filters keys: q, especie, categoria, marca.
 */
function pub_productos(string $area, array $filters = []): array
{
    $sql = "SELECT p.* FROM productos p WHERE p.estado = 'published' AND p.area_negocio = ?";
    $params = [$area];

    foreach (['categoria' => 'categoria', 'marca' => 'marca'] as $key => $col) {
        if (!empty($filters[$key])) {
            $sql .= " AND p.$col = ?";
            $params[] = $filters[$key];
        }
    }
    // Un producto puede estar en varias especies: alcanza con que exista el vínculo.
    if (!empty($filters['especie'])) {
        $sql .= ' AND EXISTS (SELECT 1 FROM producto_especies pe
                              WHERE pe.producto_id = p.id AND pe.especie = ?)';
        $params[] = $filters['especie'];
    }
    if (!empty($filters['q'])) {
        $sql .= ' AND (p.nombre LIKE ? OR p.categoria LIKE ? OR p.marca LIKE ?
                       OR EXISTS (SELECT 1 FROM producto_especies pe
                                  WHERE pe.producto_id = p.id AND pe.especie LIKE ?))';
        $like = '%' . $filters['q'] . '%';
        array_push($params, $like, $like, $like, $like);
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

/* ------------------------------------------------- sidebar filter helpers */

/** Read the active sidebar filters from the query string. */
function active_filters(): array
{
    return [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'especie' => trim((string) ($_GET['especie'] ?? '')),
        'categoria' => trim((string) ($_GET['categoria'] ?? '')),
        'marca' => trim((string) ($_GET['marca'] ?? '')),
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
        'categoria' => $_GET['categoria'] ?? null,
        'marca' => $_GET['marca'] ?? null,
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

function render_product_card(array $item, string $detailBase): string
{
    $img = asset($item['imagen']) ?: asset('assets/images/products/grid/1.png');
    $href = $detailBase . '?id=' . (int) $item['id'];
    $nombre = e($item['nombre']);
    return '
      <div class="col-12 col-md-6 col-lg-4" data-product-card>
        <div class="product-item">
          <div class="product-img">
            <img src="' . e($img) . '" alt="' . $nombre . '"/>
            <a class="ver-detalle js-open-product" href="' . e($href) . '"><i class="fas fa-eye"></i> ' . e(t('common.view_details')) . '</a>
            <div class="badge"></div>
          </div>
          <div class="product-content">
            <div class="product-title"><a class="js-open-product" href="' . e($href) . '">' . $nombre . '</a></div>
          </div>
        </div>
      </div>';
}

function render_recent_product(array $item, string $detailBase): string
{
    $img = asset($item['imagen']) ?: asset('assets/images/products/thumb/1.jpg');
    $href = $detailBase . '?id=' . (int) $item['id'];
    return '
        <div class="product">
          <div class="product-img"><img src="' . e($img) . '" alt="product"/></div>
          <div class="product-desc"><div class="product-title"><a class="js-open-product" href="' . e($href) . '">' . e($item['nombre']) . '</a></div></div>
        </div>';
}

/**
 * Render a sidebar filter <ul> for one column (especie|categoria|marca).
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

/**
 * A single noticia formatted as an owl-carousel slide, matching the
 * "Artículos y Novedades Recientes" markup on the area pages.
 */
function render_noticia_slide(array $item, string $detailBase): string
{
    $d = date_parts($item['published_at'] ?: $item['created_at']);
    $img = asset($item['imagen']) ?: asset('assets/images/blog/grid/1.jpg');
    $href = $detailBase . '?id=' . (int) $item['id'];
    return '
            <div>
              <div class="blog-entry" data-hover="">
                <div class="entry-img">
                  <div class="entry-date">
                    <div class="entry-content"><span class="day">' . e($d['day']) . '</span><span class="month">' . e($d['month']) . '</span><span class="year">' . e($d['year']) . '</span></div>
                  </div>
                   <a href="' . e($href) . '"><img src="' . e($img) . '" alt="' . e($item['titulo']) . '"/></a>
                </div>
                <div class="entry-content">
                  <div class="entry-meta">
                    <div class="entry-category"><a href="javascript:void(0)">' . e($item['categoria']) . '</a></div>
                  </div>
                  <div class="entry-title">
                    <h4><a href="' . e($href) . '">' . e($item['titulo']) . '</a></h4>
                  </div>
                  <div class="entry-bio">
                    <p>' . e($item['extracto']) . '</p>
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
    return '
      <div class="col-12 col-md-6 col-lg-4">
        <div class="blog-entry" data-hover="">
          <div class="entry-img">
            <div class="entry-date">
              <div class="entry-content"><span class="day">' . e($d['day']) . '</span><span class="month">' . e($d['month']) . '</span><span class="year">' . e($d['year']) . '</span></div>
            </div>
            <a href="' . e($href) . '"><img src="' . e($img) . '" alt="' . e($item['titulo']) . '"/></a>
          </div>
          <div class="entry-content">
            <div class="entry-meta">
              <div class="entry-category"><a href="javascript:void(0)">' . e($item['categoria']) . '</a></div>
            </div>
            <div class="entry-title">
              <h4><a href="' . e($href) . '">' . e($item['titulo']) . '</a></h4>
            </div>
            <div class="entry-bio"><p>' . e($item['extracto']) . '</p></div>
            <div class="entry-more">
              <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="' . e($href) . '">
                <div class="line"><span></span></div><span>' . e(t('common.see_more')) . '</span>
              </a>
            </div>
          </div>
        </div>
      </div>';
}
