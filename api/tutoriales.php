<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$method = request_method();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

function tutorial_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'titulo' => $row['titulo'],
        'titulo_en' => $row['titulo_en'],
        'descripcion' => $row['descripcion'],
        'descripcion_en' => $row['descripcion_en'],
        'youtube_url' => $row['youtube_url'],
        'youtube_id' => $row['youtube_id'],
        'estado' => $row['estado'],
        'autor_id' => $row['autor_id'] !== null ? (int) $row['autor_id'] : null,
        'autor_username' => $row['autor_username'] ?? null,
        'vistas' => (int) $row['vistas'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'published_at' => $row['published_at'],
    ];
}

function validate_tutorial(array $body, bool $partial = false): array
{
    $data = [];
    if (!$partial || array_key_exists('titulo', $body)) {
        $data['titulo'] = sanitize_text($body['titulo'] ?? '');
        if ($data['titulo'] === '') {
            json_error('El título es obligatorio');
        }
    }
    if (!$partial || array_key_exists('titulo_en', $body)) {
        $data['titulo_en'] = sanitize_text($body['titulo_en'] ?? '');
    }
    if (!$partial || array_key_exists('descripcion', $body)) {
        $data['descripcion'] = sanitize_html($body['descripcion'] ?? '');
    }
    if (!$partial || array_key_exists('descripcion_en', $body)) {
        $data['descripcion_en'] = sanitize_html($body['descripcion_en'] ?? '');
    }
    if (!$partial || array_key_exists('youtube_url', $body)) {
        $url = sanitize_text($body['youtube_url'] ?? '');
        $ytId = extraer_youtube_id($url);
        if ($url !== '' && !$ytId) {
            json_error('La URL de YouTube no es válida');
        }
        $data['youtube_url'] = $url;
        $data['youtube_id'] = $ytId ?? '';
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
        'SELECT t.*, u.username AS autor_username FROM tutoriales t
         LEFT JOIN users u ON u.id = t.autor_id WHERE t.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error('Tutorial no encontrado', 404);
    }

    $user = current_user();
    if ($row['estado'] !== 'published' && !$user) {
        json_error('Tutorial no encontrado', 404);
    }

    if (!$user && $row['estado'] === 'published') {
        db()->prepare('UPDATE tutoriales SET vistas = vistas + 1 WHERE id = ?')->execute([$id]);
        $row['vistas'] = (int) $row['vistas'] + 1;
    }

    json_response(['ok' => true, 'item' => tutorial_row($row)]);
}

if ($method === 'GET') {
    $user = current_user();
    $q = sanitize_text($_GET['q'] ?? '');
    $sql = 'SELECT t.*, u.username AS autor_username FROM tutoriales t LEFT JOIN users u ON u.id = t.autor_id WHERE 1=1';
    $params = [];

    if (!$user) {
        $sql .= " AND t.estado = 'published'";
    } elseif (!empty($_GET['estado']) && in_array($_GET['estado'], ESTADOS, true)) {
        $sql .= ' AND t.estado = ?';
        $params[] = $_GET['estado'];
    }

    if ($q !== '') {
        $sql .= ' AND (t.titulo LIKE ? OR t.descripcion LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY COALESCE(t.published_at, t.created_at) DESC, t.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $items = array_map('tutorial_row', $stmt->fetchAll());
    json_response(['ok' => true, 'items' => $items, 'total' => count($items)]);
}

if ($method === 'POST') {
    $user = require_auth();
    $body = read_json_body();
    require_csrf($body);
    $data = validate_tutorial($body);

    if (empty($data['youtube_id'])) {
        json_error('La URL de YouTube es obligatoria');
    }

    // Traducción automática: si no se cargó a mano la versión en inglés,
    // se genera sola a partir del español. La descripción del tutorial es
    // texto plano (no viene del editor Quill), por eso no se envuelve en <p>.
    if (($data['titulo_en'] ?? '') === '') {
        $data['titulo_en'] = auto_traducir($data['titulo']);
    }
    if (($data['descripcion_en'] ?? '') === '') {
        $data['descripcion_en'] = auto_traducir($data['descripcion'] ?? '');
    }

    $publishedAt = $data['estado'] === 'published' ? now_sql() : null;
    $stmt = db()->prepare(
        'INSERT INTO tutoriales (titulo, titulo_en, descripcion, descripcion_en, youtube_url, youtube_id, estado, autor_id, published_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['titulo'],
        $data['titulo_en'] ?? '',
        $data['descripcion'] ?? '',
        $data['descripcion_en'] ?? '',
        $data['youtube_url'],
        $data['youtube_id'],
        $data['estado'],
        $user['id'],
        $publishedAt,
        now_sql(),
    ]);
    $newId = (int) db()->lastInsertId();
    $stmt = db()->prepare(
        'SELECT t.*, u.username AS autor_username FROM tutoriales t LEFT JOIN users u ON u.id = t.autor_id WHERE t.id = ?'
    );
    $stmt->execute([$newId]);
    json_response(['ok' => true, 'item' => tutorial_row($stmt->fetch())], 201);
}

if ($method === 'PUT' || $method === 'PATCH') {
    require_auth();
    if ($id <= 0) {
        json_error('ID requerido');
    }
    $body = read_json_body();
    require_csrf($body);

    $existing = db()->prepare('SELECT * FROM tutoriales WHERE id = ?');
    $existing->execute([$id]);
    $row = $existing->fetch();
    if (!$row) {
        json_error('Tutorial no encontrado', 404);
    }

    $data = validate_tutorial($body, true);
    $titulo = $data['titulo'] ?? $row['titulo'];
    $tituloEn = $data['titulo_en'] ?? $row['titulo_en'];
    $descripcion = $data['descripcion'] ?? $row['descripcion'];
    $descripcionEn = $data['descripcion_en'] ?? $row['descripcion_en'];
    $youtubeUrl = $data['youtube_url'] ?? $row['youtube_url'];
    $youtubeId = $data['youtube_id'] ?? $row['youtube_id'];
    $estado = $data['estado'] ?? $row['estado'];

    if ($estado === 'published' && $youtubeId === '') {
        json_error('La URL de YouTube es obligatoria para publicar');
    }

    // Traducción automática: si el campo en inglés quedó vacío, se genera
    // solo a partir del español (no pisa una traducción ya cargada a mano).
    if ($tituloEn === '') {
        $tituloEn = auto_traducir($titulo);
    }
    if ($descripcionEn === '') {
        $descripcionEn = auto_traducir($descripcion);
    }

    $publishedAt = $row['published_at'];
    if ($estado === 'published' && !$publishedAt) {
        $publishedAt = now_sql();
    }

    $stmt = db()->prepare(
        'UPDATE tutoriales SET titulo=?, titulo_en=?, descripcion=?, descripcion_en=?, youtube_url=?, youtube_id=?, estado=?, published_at=?, updated_at=? WHERE id=?'
    );
    $stmt->execute([$titulo, $tituloEn, $descripcion, $descripcionEn, $youtubeUrl, $youtubeId, $estado, $publishedAt, now_sql(), $id]);

    $stmt = db()->prepare(
        'SELECT t.*, u.username AS autor_username FROM tutoriales t LEFT JOIN users u ON u.id = t.autor_id WHERE t.id = ?'
    );
    $stmt->execute([$id]);
    json_response(['ok' => true, 'item' => tutorial_row($stmt->fetch())]);
}

if ($method === 'DELETE') {
    require_auth();
    if ($id <= 0) {
        json_error('ID requerido');
    }
    $body = read_json_body();
    require_csrf($body);

    $stmt = db()->prepare('DELETE FROM tutoriales WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        json_error('Tutorial no encontrado', 404);
    }
    json_response(['ok' => true]);
}

json_error('Método no permitido', 405);
