<?php
declare(strict_types=1);

session_start();

header('X-Content-Type-Options: nosniff');

const AREAS = ['Nutricion Animal', 'Pharma', 'VetPharma'];
const ESPECIES = ['Aves', 'Porcinos', 'Ganadería', 'Mascotas', 'Lechería', 'Equinos', 'Ovinos'];
const ESTADOS = ['draft', 'published'];

/**
 * Alta de usuarios desde el sitio. Está cerrada: las cuentas se crean a mano.
 * Poner en true vuelve a habilitar /admin/register.php y la acción `register`
 * de la API; no hace falta tocar nada más.
 */
const REGISTRO_HABILITADO = false;

function root_path(): string
{
    return dirname(__DIR__);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dataDir = root_path() . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $dbFile = $dataDir . '/insalcor.sqlite';
    $isNew = !file_exists($dbFile);

    $pdo = new PDO('sqlite:' . $dbFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew || needs_schema($pdo)) {
        $schema = file_get_contents($dataDir . '/schema.sql');
        $pdo->exec($schema);
    }

    return $pdo;
}

function needs_schema(PDO $pdo): bool
{
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    return $stmt->fetch() === false;
}

function json_response(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $message, int $status = 400): void
{
    json_response(['ok' => false, 'error' => $message], $status);
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, username, created_at FROM users WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        json_error('No autenticado', 401);
    }
    return $user;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(?array $body = null): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? ($_POST['csrf_token'] ?? ''));
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $token)) {
        json_error('CSRF inválido', 403);
    }
}

function sanitize_html(?string $html): string
{
    if ($html === null || $html === '') {
        return '';
    }

    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><h1><h2><h3><h4><blockquote><span>';
    $clean = strip_tags($html, $allowed);

    // Strip event handlers and javascript: URLs
    $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
    $clean = preg_replace('/href\s*=\s*("|\')\s*javascript:[^"\']*\1/i', 'href="#"', $clean) ?? $clean;

    return $clean;
}

function sanitize_text(?string $value): string
{
    return trim(strip_tags((string) $value));
}

function now_sql(): string
{
    return gmdate('Y-m-d H:i:s');
}

/**
 * Extrae el ID de un video de YouTube desde cualquier formato de URL usual:
 * youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID, youtube.com/shorts/ID.
 * Devuelve null si no se pudo reconocer.
 */
function extraer_youtube_id(?string $url): ?string
{
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }
    $patrones = [
        '~youtu\.be/([A-Za-z0-9_-]{11})~',
        '~[?&]v=([A-Za-z0-9_-]{11})~',
        '~youtube\.com/embed/([A-Za-z0-9_-]{11})~',
        '~youtube\.com/shorts/([A-Za-z0-9_-]{11})~',
    ];
    foreach ($patrones as $patron) {
        if (preg_match($patron, $url, $m)) {
            return $m[1];
        }
    }
    // Por si ya viene solo el ID (11 caracteres, sin espacios ni barras).
    if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url)) {
        return $url;
    }
    return null;
}

/* ------------------------------------------------------ traducción automática */

/**
 * Traduce un fragmento de texto plano (sin HTML) del español al inglés
 * usando la API pública y gratuita de MyMemory (no requiere API key).
 * Devuelve null si falla (sin conexión, límite superado, respuesta rara,
 * etc.) — nunca lanza una excepción, para no cortar el guardado del
 * contenido si la traducción automática no está disponible en el momento.
 *
 * MyMemory acepta un máximo razonable de caracteres por consulta; los
 * textos largos hay que partirlos antes de llamar a esta función
 * (ver auto_traducir_fragmentos()).
 */
function traducir_mymemory_fragmento(string $texto, string $de = 'es', string $a = 'en'): ?string
{
    $texto = trim($texto);
    if ($texto === '') {
        return '';
    }

    $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q' => $texto,
        'langpair' => "$de|$a",
    ]);

    $context = stream_context_create([
        'http' => ['timeout' => 8, 'ignore_errors' => true],
        'https' => ['timeout' => 8, 'ignore_errors' => true],
    ]);

    $respuesta = @file_get_contents($url, false, $context);
    if ($respuesta === false) {
        return null;
    }

    $json = json_decode($respuesta, true);
    $estado = (int) ($json['responseStatus'] ?? 0);
    $traduccion = $json['responseData']['translatedText'] ?? null;

    if ($estado !== 200 || !is_string($traduccion) || $traduccion === '') {
        return null;
    }
    // MyMemory a veces devuelve un aviso de cuota como si fuera la traducción.
    if (stripos($traduccion, 'MYMEMORY WARNING') !== false) {
        return null;
    }

    return $traduccion;
}

/**
 * Parte un texto en fragmentos de a lo sumo $max caracteres, cortando en
 * límites de oración cuando se puede (para no cortar una idea a la mitad).
 *
 * @return string[]
 */
function dividir_en_fragmentos(string $texto, int $max = 480): array
{
    $texto = trim($texto);
    if ($texto === '') {
        return [];
    }
    if (mb_strlen($texto) <= $max) {
        return [$texto];
    }

    $oraciones = preg_split('/(?<=[.!?])\s+/u', $texto) ?: [$texto];
    $fragmentos = [];
    $actual = '';
    foreach ($oraciones as $oracion) {
        if ($actual !== '' && mb_strlen($actual . ' ' . $oracion) > $max) {
            $fragmentos[] = trim($actual);
            $actual = $oracion;
        } else {
            $actual = $actual === '' ? $oracion : $actual . ' ' . $oracion;
        }
    }
    if ($actual !== '') {
        $fragmentos[] = trim($actual);
    }

    // Si una oración sola ya supera el límite, la cortamos a la fuerza.
    $final = [];
    foreach ($fragmentos as $f) {
        if (mb_strlen($f) <= $max) {
            $final[] = $f;
            continue;
        }
        foreach (mb_str_split($f, $max) as $trozo) {
            $final[] = $trozo;
        }
    }
    return $final;
}

/**
 * Traduce un texto plano (título, extracto, nombre de producto...) del
 * español al inglés. Devuelve '' si no se pudo traducir (sin conexión,
 * límite superado, etc.) — el llamador debe tratar '' como "dejar vacío,
 * ya se completará a mano o en un guardado posterior".
 */
function auto_traducir(?string $texto): string
{
    $texto = trim((string) $texto);
    if ($texto === '') {
        return '';
    }

    $fragmentos = dividir_en_fragmentos($texto);
    $traducidos = [];
    foreach ($fragmentos as $fragmento) {
        $t = traducir_mymemory_fragmento($fragmento);
        if ($t === null) {
            return ''; // preferimos no guardar una traducción a medias
        }
        $traducidos[] = $t;
    }
    return implode(' ', $traducidos);
}

/**
 * Traduce contenido HTML simple (el que genera el editor Quill: párrafos,
 * negrita, listas, links) del español al inglés. Traduce párrafo por
 * párrafo como texto plano y los vuelve a envolver en <p>; el formato
 * (negrita, listas, etc.) se pierde en la traducción automática — si hace
 * falta conservarlo, el editor puede ajustar el campo en inglés a mano
 * desde el admin.
 */
function auto_traducir_html(?string $html): string
{
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }

    $bloques = preg_split('~</p>|<br\s*/?>~i', $html) ?: [$html];
    $parrafos = [];
    foreach ($bloques as $bloque) {
        $plano = trim(strip_tags($bloque));
        if ($plano !== '') {
            $parrafos[] = $plano;
        }
    }
    if (!$parrafos) {
        return '';
    }

    $traducidos = [];
    foreach ($parrafos as $parrafo) {
        $t = auto_traducir($parrafo);
        if ($t === '') {
            return ''; // preferimos no guardar una traducción a medias
        }
        $traducidos[] = '<p>' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    return implode('', $traducidos);
}
