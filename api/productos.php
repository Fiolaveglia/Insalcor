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
        'nombre_en' => $row['nombre_en'],
        'descripcion' => $row['descripcion'],
        'descripcion_en' => $row['descripcion_en'],
        'imagen' => $row['imagen'],
        'area_negocio' => $row['area_negocio'],
        'especies' => especies_de((int) $row['id']),
        'nombre_comercial' => $row['nombre_comercial'],
        'representada' => $row['representada'],
        'categoria' => $row['categoria'],
        'subgrupo' => $row['subgrupo'],
        'paises' => paises_de((int) $row['id']),
        'ficha_tecnica' => $row['ficha_tecnica'],
        'nota_blog' => $row['nota_blog'],
        'estado' => $row['estado'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];
}

/** @return string[] Especies asociadas a un producto. */
function especies_de(int $productoId): array
{
    $stmt = db()->prepare('SELECT especie FROM producto_especies WHERE producto_id = ? ORDER BY especie');
    $stmt->execute([$productoId]);
    return array_column($stmt->fetchAll(), 'especie');
}

/**
 * Normaliza y valida el array de especies del body.
 * Catálogo único: aplica igual para las 3 áreas de negocio.
 *
 * @return string[]
 */
function validar_especies(mixed $valor): array
{
    if ($valor === null || $valor === '') {
        return [];
    }
    // Se acepta un string suelto por compatibilidad con clientes viejos.
    $lista = is_array($valor) ? $valor : [$valor];

    $out = [];
    foreach ($lista as $e) {
        $e = sanitize_text(is_string($e) ? $e : '');
        if ($e === '') {
            continue;
        }
        if (!in_array($e, ESPECIES, true)) {
            json_error("Especie inválida: $e");
        }
        $out[$e] = true;
    }
    return array_keys($out);
}

/** Reemplaza el conjunto de especies de un producto. @param string[] $especies */
function guardar_especies(int $productoId, array $especies): void
{
    $del = db()->prepare('DELETE FROM producto_especies WHERE producto_id = ?');
    $del->execute([$productoId]);
    if (!$especies) {
        return;
    }
    $ins = db()->prepare('INSERT INTO producto_especies (producto_id, especie) VALUES (?, ?)');
    foreach ($especies as $e) {
        $ins->execute([$productoId, $e]);
    }
}

/** @return string[] Países en los que se comercializa un producto. */
function paises_de(int $productoId): array
{
    $stmt = db()->prepare('SELECT pais FROM producto_paises WHERE producto_id = ? ORDER BY pais');
    $stmt->execute([$productoId]);
    return array_column($stmt->fetchAll(), 'pais');
}

/**
 * Normaliza y valida el array de países del body (catálogo Pharma /
 * VetPharma / Suplementos).
 *
 * @return string[]
 */
function validar_paises(mixed $valor): array
{
    if ($valor === null || $valor === '') {
        return [];
    }
    $lista = is_array($valor) ? $valor : [$valor];

    $out = [];
    foreach ($lista as $p) {
        $p = sanitize_text(is_string($p) ? $p : '');
        if ($p === '') {
            continue;
        }
        if (!in_array($p, PAISES, true)) {
            json_error("País inválido: $p");
        }
        $out[$p] = true;
    }
    return array_keys($out);
}

/** Reemplaza el conjunto de países de un producto. @param string[] $paises */
function guardar_paises(int $productoId, array $paises): void
{
    $del = db()->prepare('DELETE FROM producto_paises WHERE producto_id = ?');
    $del->execute([$productoId]);
    $ins = db()->prepare('INSERT INTO producto_paises (producto_id, pais) VALUES (?, ?)');
    foreach ($paises as $p) {
        $ins->execute([$productoId, $p]);
    }
}

/**
 * La categoría depende del área: en Pharma / VetPharma / Suplementos es
 * obligatoria y tiene que ser una de CATEGORIAS_POR_AREA; Nutrición no usa
 * categoría (se clasifica por especie), así que queda vacía.
 */
function validar_categoria(string $categoria, string $area): string
{
    $validas = CATEGORIAS_POR_AREA[$area] ?? [];
    if (!$validas) {
        return '';
    }
    if ($categoria === '') {
        json_error('La categoría es obligatoria en ' . $area);
    }
    if (!in_array($categoria, $validas, true)) {
        json_error("Categoría inválida para $area: $categoria");
    }
    return $categoria;
}

/** Mensaje del 409: la identidad es nombre + nombre comercial + representada + área. */
const MSG_PRODUCTO_DUPLICADO = 'Ya existe un producto con ese nombre, nombre comercial y representada en esa área de negocio';

function validate_producto(array $body, bool $partial = false): array
{
    $data = [];

    if (!$partial || array_key_exists('nombre', $body)) {
        $data['nombre'] = sanitize_text($body['nombre'] ?? '');
        if ($data['nombre'] === '') {
            json_error('El nombre es obligatorio');
        }
    }
    if (!$partial || array_key_exists('nombre_en', $body)) {
        $data['nombre_en'] = sanitize_text($body['nombre_en'] ?? '');
    }
    if (!$partial || array_key_exists('descripcion', $body)) {
        $data['descripcion'] = sanitize_html($body['descripcion'] ?? '');
    }
    if (!$partial || array_key_exists('descripcion_en', $body)) {
        $data['descripcion_en'] = sanitize_html($body['descripcion_en'] ?? '');
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
    if (!$partial || array_key_exists('ficha_tecnica', $body)) {
        $data['ficha_tecnica'] = sanitize_text($body['ficha_tecnica'] ?? '');
    }
    if (!$partial || array_key_exists('nota_blog', $body)) {
        $data['nota_blog'] = sanitize_text($body['nota_blog'] ?? '');
    }
    foreach (['nombre_comercial', 'representada', 'categoria', 'subgrupo'] as $campo) {
        if (!$partial || array_key_exists($campo, $body)) {
            $data[$campo] = sanitize_text($body[$campo] ?? '');
        }
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
        $sql .= ' AND (nombre LIKE ? OR nombre_comercial LIKE ? OR representada LIKE ? OR categoria LIKE ?
                       OR EXISTS (SELECT 1 FROM producto_especies pe
                                  WHERE pe.producto_id = productos.id AND pe.especie LIKE ?))';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like);
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

    $especies = validar_especies($body['especies'] ?? ($body['especie'] ?? null));
    $paises = validar_paises($body['paises'] ?? null);
    $data['categoria'] = validar_categoria($data['categoria'] ?? '', $data['area_negocio']);

    // Traducción automática: si no se cargó a mano la versión en inglés,
    // se genera sola a partir del español.
    if (($data['nombre_en'] ?? '') === '') {
        $data['nombre_en'] = auto_traducir($data['nombre']);
    }
    if (($data['descripcion_en'] ?? '') === '') {
        $data['descripcion_en'] = auto_traducir_html($data['descripcion'] ?? '');
    }

    db()->beginTransaction();
    try {
        $stmt = db()->prepare(
            'INSERT INTO productos (nombre, nombre_en, nombre_comercial, representada, categoria, subgrupo,
                                    descripcion, descripcion_en, imagen, area_negocio, ficha_tecnica, nota_blog, estado, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['nombre'],
            $data['nombre_en'] ?? '',
            $data['nombre_comercial'] ?? '',
            $data['representada'] ?? '',
            $data['categoria'],
            $data['subgrupo'] ?? '',
            $data['descripcion'] ?? '',
            $data['descripcion_en'] ?? '',
            $data['imagen'] ?? '',
            $data['area_negocio'],
            $data['ficha_tecnica'] ?? '',
            $data['nota_blog'] ?? '',
            $data['estado'] ?? 'draft',
            now_sql(),
        ]);
        $newId = (int) db()->lastInsertId();
        guardar_especies($newId, $especies);
        guardar_paises($newId, $paises);
        db()->commit();
    } catch (PDOException $e) {
        db()->rollBack();
        if (str_contains($e->getMessage(), 'UNIQUE constraint failed: productos.nombre')) {
            json_error(MSG_PRODUCTO_DUPLICADO, 409);
        }
        throw $e;
    }

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

    $nombre = $data['nombre'] ?? $row['nombre'];
    $nombreEn = $data['nombre_en'] ?? $row['nombre_en'];
    $descripcion = $data['descripcion'] ?? $row['descripcion'];
    $descripcionEn = $data['descripcion_en'] ?? $row['descripcion_en'];
    $imagen = $data['imagen'] ?? $row['imagen'];
    $area = $data['area_negocio'] ?? $row['area_negocio'];
    $ficha = $data['ficha_tecnica'] ?? $row['ficha_tecnica'];
    $notaBlog = $data['nota_blog'] ?? $row['nota_blog'];
    $estado = $data['estado'] ?? $row['estado'];
    $nombreComercial = $data['nombre_comercial'] ?? $row['nombre_comercial'];
    $representada = $data['representada'] ?? $row['representada'];
    $subgrupo = $data['subgrupo'] ?? $row['subgrupo'];
    // La categoría se revalida contra el área final (puede haber cambiado
    // sólo el área, o sólo la categoría).
    $categoria = validar_categoria($data['categoria'] ?? $row['categoria'], $area);

    // Los países, igual que las especies, sólo se tocan si vienen en el body.
    $tocaPaises = array_key_exists('paises', $body);
    $paises = validar_paises($body['paises'] ?? null);

    // Las especies sólo se tocan si vienen en el body.
    $tocaEspecies = array_key_exists('especies', $body) || array_key_exists('especie', $body);
    $especies = validar_especies($body['especies'] ?? ($body['especie'] ?? null));

    // Traducción automática: si el campo en inglés quedó vacío, se genera
    // solo a partir del español (no pisa una traducción ya cargada a mano).
    if ($nombreEn === '') {
        $nombreEn = auto_traducir($nombre);
    }
    if ($descripcionEn === '') {
        $descripcionEn = auto_traducir_html($descripcion);
    }

    db()->beginTransaction();
    try {
        $stmt = db()->prepare(
            'UPDATE productos SET nombre=?, nombre_en=?, nombre_comercial=?, representada=?, categoria=?, subgrupo=?,
                                  descripcion=?, descripcion_en=?, imagen=?, area_negocio=?, ficha_tecnica=?, nota_blog=?, estado=?, updated_at=?
             WHERE id=?'
        );
        $stmt->execute([$nombre, $nombreEn, $nombreComercial, $representada, $categoria, $subgrupo,
                        $descripcion, $descripcionEn, $imagen, $area, $ficha, $notaBlog, $estado, now_sql(), $id]);
        if ($tocaEspecies) {
            guardar_especies($id, $especies);
        }
        if ($tocaPaises) {
            guardar_paises($id, $paises);
        }
        db()->commit();
    } catch (PDOException $e) {
        db()->rollBack();
        if (str_contains($e->getMessage(), 'UNIQUE constraint failed: productos.nombre')) {
            json_error(MSG_PRODUCTO_DUPLICADO, 409);
        }
        throw $e;
    }

    $stmt = db()->prepare('SELECT * FROM productos WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['ok' => true, 'item' => producto_row($stmt->fetch())]);
}

if ($method === 'DELETE') {
    require_auth();
    $body = read_json_body();
    require_csrf($body);

    if ($id <= 0) {
        json_error('ID requerido');
    }

    $stmt = db()->prepare('DELETE FROM productos WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        json_error('Producto no encontrado', 404);
    }
    json_response(['ok' => true]);
}

json_error('Método no permitido', 405);