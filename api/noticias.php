<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$method = request_method();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

function noticia_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'titulo' => $row['titulo'],
        'extracto' => $row['extracto'],
        'contenido' => $row['contenido'],
        'imagen' => $row['imagen'],
        'categoria' => $row['categoria'],
        'estado' => $row['estado'],
        'autor_id' => $row['autor_id'] !== null ? (int) $row['autor_id'] : null,
        'autor_username' => $row['autor_username'] ?? null,
        'vistas' => (int) $row['vistas'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'published_at' => $row['published_at'],
    ];
}

function validate_noticia(array $body, bool $partial = false): array
{
    $data = [];
    if (!$partial || array_key_exists('titulo', $body)) {
        $data['titulo'] = sanitize_text($body['titulo'] ?? '');
        if ($data['titulo'] === '') {
            json_error('El título es obligatorio');
        }
    }
    if (!$partial || array_key_exists('extracto', $body)) {
        $data['extracto'] = sanitize_text($body['extracto'] ?? '');
    }
    if (!$partial || array_key_exists('contenido', $body)) {
        $data['contenido'] = sanitize_html($body['contenido'] ?? '');
    }
    if (!$partial || array_key_exists('imagen', $body)) {
        $data['imagen'] = sanitize_text($body['imagen'] ?? '');
    }
    if (!$partial || array_key_exists('categoria', $body)) {
        $data['categoria'] = sanitize_text($body['categoria'] ?? '');
    }
    if (!$partial || array_key_exists('estado', $body)) {
        $estado = sanitize_text($body['estado'] ?? 'draft');
        if (!in_array($estado, ESTADOS, true)) {
            json_error('Estado inválido');
        }
        $data['estado'] = $estado;
    }
    return $data;
}

if ($method === 'GET' && $id > 0) {
    $stmt = db()->prepare(
        'SELECT n.*, u.username AS autor_username FROM noticias n
         LEFT JOIN users u ON u.id = n.autor_id WHERE n.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error('Noticia no encontrada', 404);
    }

    $user = current_user();
    if ($row['estado'] !== 'published' && !$user) {
        json_error('Noticia no encontrada', 404);
    }

    if (!$user && $row['estado'] === 'published') {
        db()->prepare('UPDATE noticias SET vistas = vistas + 1 WHERE id = ?')->execute([$id]);
        $row['vistas'] = (int) $row['vistas'] + 1;
    }

    json_response(['ok' => true, 'item' => noticia_row($row)]);
}

if ($method === 'GET') {
    $user = current_user();
    $q = sanitize_text($_GET['q'] ?? '');
    $sql = 'SELECT n.*, u.username AS autor_username FROM noticias n LEFT JOIN users u ON u.id = n.autor_id WHERE 1=1';
    $params = [];

    if (!$user) {
        $sql .= " AND n.estado = 'published'";
    } elseif (!empty($_GET['estado']) && in_array($_GET['estado'], ESTADOS, true)) {
        $sql .= ' AND n.estado = ?';
        $params[] = $_GET['estado'];
    }

    if ($q !== '') {
        $sql .= ' AND (n.titulo LIKE ? OR n.extracto LIKE ? OR n.categoria LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY COALESCE(n.published_at, n.created_at) DESC, n.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $items = array_map('noticia_row', $stmt->fetchAll());
    json_response(['ok' => true, 'items' => $items, 'total' => count($items)]);
}

if ($method === 'POST') {
    $user = require_auth();
    $body = read_json_body();
    require_csrf($body);
    $data = validate_noticia($body);

    $publishedAt = $data['estado'] === 'published' ? now_sql() : null;
    $stmt = db()->prepare(
        'INSERT INTO noticias (titulo, extracto, contenido, imagen, categoria, estado, autor_id, published_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['titulo'],
        $data['extracto'] ?? '',
        $data['contenido'] ?? '',
        $data['imagen'] ?? '',
        $data['categoria'] ?? '',
        $data['estado'],
        $user['id'],
        $publishedAt,
        now_sql(),
    ]);
    $newId = (int) db()->lastInsertId();
    $stmt = db()->prepare(
        'SELECT n.*, u.username AS autor_username FROM noticias n LEFT JOIN users u ON u.id = n.autor_id WHERE n.id = ?'
    );
    $stmt->execute([$newId]);
    json_response(['ok' => true, 'item' => noticia_row($stmt->fetch())], 201);
}

if ($method === 'PUT' || $method === 'PATCH') {
    $user = require_auth();
    if ($id <= 0) {
        json_error('ID requerido');
    }
    $body = read_json_body();
    require_csrf($body);

    $existing = db()->prepare('SELECT * FROM noticias WHERE id = ?');
    $existing->execute([$id]);
    $row = $existing->fetch();
    if (!$row) {
        json_error('Noticia no encontrada', 404);
    }

    $data = validate_noticia($body, true);
    $titulo = $data['titulo'] ?? $row['titulo'];
    $extracto = $data['extracto'] ?? $row['extracto'];
    $contenido = $data['contenido'] ?? $row['contenido'];
    $imagen = $data['imagen'] ?? $row['imagen'];
    $categoria = $data['categoria'] ?? $row['categoria'];
    $estado = $data['estado'] ?? $row['estado'];

    $publishedAt = $row['published_at'];
    if ($estado === 'published' && !$publishedAt) {
        $publishedAt = now_sql();
    }
    if ($estado === 'draft') {
        // keep published_at history or clear — keep for audit
    }

    $stmt = db()->prepare(
        'UPDATE noticias SET titulo=?, extracto=?, contenido=?, imagen=?, categoria=?, estado=?, published_at=?, updated_at=? WHERE id=?'
    );
    $stmt->execute([$titulo, $extracto, $contenido, $imagen, $categoria, $estado, $publishedAt, now_sql(), $id]);

    $stmt = db()->prepare(
        'SELECT n.*, u.username AS autor_username FROM noticias n LEFT JOIN users u ON u.id = n.autor_id WHERE n.id = ?'
    );
    $stmt->execute([$id]);
    json_response(['ok' => true, 'item' => noticia_row($stmt->fetch())]);
}

if ($method === 'DELETE') {
    require_auth();
    if ($id <= 0) {
        json_error('ID requerido');
    }
    $body = read_json_body();
    require_csrf($body);

    $stmt = db()->prepare('DELETE FROM noticias WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        json_error('Noticia no encontrada', 404);
    }
    json_response(['ok' => true]);
}

json_error('Método no permitido', 405);
