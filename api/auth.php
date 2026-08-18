<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$method = request_method();

/**
 * Normaliza el nombre de usuario: sin espacios y en minúsculas, para que
 * "Admin" y "admin" sean la misma cuenta.
 */
function normalizar_username(mixed $valor): string
{
    return strtolower(sanitize_text(is_string($valor) ? $valor : ''));
}

/** Corta la request con un error si el nombre de usuario no sirve. */
function validar_username(string $username): void
{
    if ($username === '') {
        json_error('El nombre de usuario es obligatorio');
    }
    if (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
        json_error('El nombre de usuario debe tener entre 3 y 30 caracteres');
    }
    if (!preg_match('/^[a-z0-9._-]+$/', $username)) {
        json_error('El nombre de usuario sólo puede tener letras, números, punto, guión y guión bajo');
    }
}

switch ($action) {
    case 'me':
        if ($method !== 'GET') {
            json_error('Método no permitido', 405);
        }
        $user = current_user();
        json_response([
            'ok' => true,
            'user' => $user,
            'csrf_token' => csrf_token(),
        ]);

    case 'register':
        if (!REGISTRO_HABILITADO) {
            json_error('El registro de usuarios está deshabilitado', 403);
        }
        if ($method !== 'POST') {
            json_error('Método no permitido', 405);
        }
        $body = read_json_body();
        $username = normalizar_username($body['username'] ?? '');
        $password = (string) ($body['password'] ?? '');
        $passwordConfirm = (string) ($body['password_confirm'] ?? '');

        validar_username($username);
        if (strlen($password) < 6) {
            json_error('La contraseña debe tener al menos 6 caracteres');
        }
        if ($password !== $passwordConfirm) {
            json_error('Las contraseñas no coinciden');
        }

        $exists = db()->prepare('SELECT id FROM users WHERE username = ?');
        $exists->execute([$username]);
        if ($exists->fetch()) {
            json_error('Este nombre de usuario ya está registrado', 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, $hash]);
        $id = (int) db()->lastInsertId();

        $_SESSION['user_id'] = $id;
        csrf_token();

        json_response([
            'ok' => true,
            'user' => ['id' => $id, 'username' => $username],
            'csrf_token' => csrf_token(),
        ], 201);

    case 'login':
        if ($method !== 'POST') {
            json_error('Método no permitido', 405);
        }
        $body = read_json_body();
        $username = normalizar_username($body['username'] ?? '');
        $password = (string) ($body['password'] ?? '');

        $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_error('Usuario o contraseña incorrectos', 401);
        }

        $_SESSION['user_id'] = (int) $user['id'];
        csrf_token();

        json_response([
            'ok' => true,
            'user' => ['id' => (int) $user['id'], 'username' => $user['username']],
            'csrf_token' => csrf_token(),
        ]);

    case 'logout':
        if ($method !== 'POST') {
            json_error('Método no permitido', 405);
        }
        $body = read_json_body();
        require_csrf($body);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
        json_response(['ok' => true]);

    default:
        json_error('Acción no encontrada', 404);
}
