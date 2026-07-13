<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$method = request_method();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

function producto_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'nombre' => $row['nombre'],
        'descripcion' => $row['descripcion'],
        'imagen' => $row['imagen'],
        'area_negocio' => $row['area_negocio'],
        'categoria' => $row['categoria'],
        'marca' => $row['marca'],
        'especie' => $row['especie'],
        'ficha_tecnica' => $row['ficha_tecnica'],
        'estado' => $row['estado'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];
}

function validate_producto(array $body, bool $partial = false): array
{
    $data = [];

    if (!$partial || array_key_exists('nombre', $body)) {
        $data['nombre'] = sanitize_text($body['nombre'] ?? '');
        if ($data['nombre'] === '') {
            json_error('El nombre es obligatorio');
        }
    }
    if (!$partial || array_key_exists('descripcion', $body)) {
        $data['descripcion'] = sanitize_html($body['descripcion'] ?? '');
    }
    if (!$partial || array_key_exists('imagen', $body)) {
        $data['imagen'] = sanitize_text($body['imagen'] ?? '');
    }
    if (!$partial || array_key_exists('area_negocio', $body)) {
        $area = sanitize_text($body['area_negocio'] ?? '');
        if (!in_array($area, AREAS, true)) {
            json_error('Área de negocio inválida');
        }
        $data['area_negocio'] = $area;
    }
    if (!$partial || array_key_exists('categoria', $body)) {
        $cat = sanitize_text($body['categoria'] ?? '');
        if ($cat !== '' && !in_array($cat, CATEGORIAS, true)) {
            json_error('Categoría inválida');
        }
        $data['categoria'] = $cat;
    }
    if (!$partial || array_key_exists('marca', $body)) {
        $marca = sanitize_text($body['marca'] ?? '');
        if ($marca !== '' && !in_array($marca, MARCAS, true)) {
            json_error('Marca inválida');
        }
        $data['marca'] = $marca;
    }
    if (!$partial || array_key_exists('ficha_tecnica', $body)) {
        $data['ficha_tecnica'] = sanitize_text($body['ficha_tecnica'] ?? '');
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

function resolve_especie(array $data, ?string $existingArea = null, ?string $existingEspecie = null): ?string
{
    $area = $data['area_negocio'] ?? $existingArea;
    $especie = array_key_exists('especie', $data)
        ? (sanitize_text($data['especie'] ?? '') ?: null)
        : $existingEspecie;

    if ($area === 'Nutricion Animal') {
        if ($especie === null || $especie === '') {
            // allow empty during draft but prefer validation when explicitly set
            return $especie;
        }
        if (!in_array($especie, ESPECIES, true)) {
            json_error('Especie inválida');
        }
        return $especie;
    }

    // Pharma / VetPharma: especie must be null
    return null;
}

if ($method === 'GET' && $id > 0) {
    $stmt = db()->prepare('SELECT * FROM productos WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error('Producto no encontrado', 404);
    }
    $user = current_user();
    if ($row['estado'] !== 'published' && !$user) {
        json_error('Producto no encontrado', 404);
    }
    json_response(['ok' => true, 'item' => producto_row($row)]);
}

if ($method === 'GET') {
    $user = current_user();
    $q = sanitize_text($_GET['q'] ?? '');
    $area = sanitize_text($_GET['area_negocio'] ?? '');
    $sql = 'SELECT * FROM productos WHERE 1=1';
    $params = [];

    if (!$user) {
        $sql .= " AND estado = 'published'";
    } elseif (!empty($_GET['estado']) && in_array($_GET['estado'], ESTADOS, true)) {
        $sql .= ' AND estado = ?';
        $params[] = $_GET['estado'];
    }

    if ($area !== '') {
        if (!in_array($area, AREAS, true)) {
            json_error('Área de negocio inválida');
        }
        $sql .= ' AND area_negocio = ?';
        $params[] = $area;
    }

    if ($q !== '') {
        $sql .= ' AND (nombre LIKE ? OR categoria LIKE ? OR marca LIKE ? OR especie LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like);
    }

    $sql .= ' ORDER BY updated_at DESC, id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $items = array_map('producto_row', $stmt->fetchAll());
    json_response(['ok' => true, 'items' => $items, 'total' => count($items)]);
}

if ($method === 'POST') {
    require_auth();
    $body = read_json_body();
    require_csrf($body);
    $data = validate_producto($body);

    if (empty($data['area_negocio'])) {
        json_error('Área de negocio es obligatoria');
    }

    // Include especie from body for resolve
    if (array_key_exists('especie', $body)) {
        $data['especie'] = $body['especie'];
    }
    $especie = resolve_especie($data);

    $stmt = db()->prepare(
        'INSERT INTO productos (nombre, descripcion, imagen, area_negocio, categoria, marca, especie, ficha_tecnica, estado, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['nombre'],
        $data['descripcion'] ?? '',
        $data['imagen'] ?? '',
        $data['area_negocio'],
        $data['categoria'] ?? '',
        $data['marca'] ?? '',
        $especie,
        $data['ficha_tecnica'] ?? '',
        $data['estado'] ?? 'draft',
        now_sql(),
    ]);
    $newId = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT * FROM productos WHERE id = ?');
    $stmt->execute([$newId]);
    json_response(['ok' => true, 'item' => producto_row($stmt->fetch())], 201);
}

if ($method === 'PUT' || $method === 'PATCH') {
    require_auth();
    if ($id <= 0) {
        json_error('ID requerido');
    }
    $body = read_json_body();
    require_csrf($body);

    $existing = db()->prepare('SELECT * FROM productos WHERE id = ?');
    $existing->execute([$id]);
    $row = $existing->fetch();
    if (!$row) {
        json_error('Producto no encontrado', 404);
    }

    $data = validate_producto($body, true);
    if (array_key_exists('especie', $body)) {
        $data['especie'] = $body['especie'];
    }

    $nombre = $data['nombre'] ?? $row['nombre'];
    $descripcion = $data['descripcion'] ?? $row['descripcion'];
    $imagen = $data['imagen'] ?? $row['imagen'];
    $area = $data['area_negocio'] ?? $row['area_negocio'];
    $categoria = $data['categoria'] ?? $row['categoria'];
    $marca = $data['marca'] ?? $row['marca'];
    $ficha = $data['ficha_tecnica'] ?? $row['ficha_tecnica'];
    $estado = $data['estado'] ?? $row['estado'];
    $especie = resolve_especie(
        array_merge(['area_negocio' => $area], $data),
        $area,
        $row['especie']
    );

    $stmt = db()->prepare(
        'UPDATE productos SET nombre=?, descripcion=?, imagen=?, area_negocio=?, categoria=?, marca=?, especie=?, ficha_tecnica=?, estado=?, updated_at=? WHERE id=?'
    );
    $stmt->execute([$nombre, $descripcion, $imagen, $area, $categoria, $marca, $especie, $ficha, $estado, now_sql(), $id]);

    $stmt = db()->prepare('SELECT * FROM productos WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['ok' => true, 'item' => producto_row($stmt->fetch())]);
}

if ($method === 'DELETE') {
    require_auth();
    if ($id <= 0) {
        json_error('ID requerido');
    }
    $body = read_json_body();
    require_csrf($body);

    $stmt = db()->prepare('DELETE FROM productos WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        json_error('Producto no encontrado', 404);
    }
    json_response(['ok' => true]);
}

json_error('Método no permitido', 405);
