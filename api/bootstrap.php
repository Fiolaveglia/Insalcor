<?php
declare(strict_types=1);

session_start();

header('X-Content-Type-Options: nosniff');

const AREAS = ['Nutricion Animal', 'Pharma', 'VetPharma'];
const CATEGORIAS = ['Premezclas', 'Aditivos', 'Correctores', 'Excipientes', 'APIS', 'Minerales', 'Vitaminas'];
const MARCAS = ['Ingredion', 'Mingtai Chemicals', 'Kerry BioScience', 'Research AG', 'Otros'];
const ESPECIES = ['Aves', 'Porcinos', 'Ganadería', 'Mascotas', 'Lechería'];
const ESTADOS = ['draft', 'published'];

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
    $stmt = db()->prepare('SELECT id, email, created_at FROM users WHERE id = ?');
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
