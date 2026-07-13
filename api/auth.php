<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$method = request_method();

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
        if ($method !== 'POST') {
            json_error('Método no permitido', 405);
        }
        $body = read_json_body();
        $email = strtolower(sanitize_text($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $passwordConfirm = (string) ($body['password_confirm'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('Email inválido');
        }
        if (strlen($password) < 6) {
            json_error('La contraseña debe tener al menos 6 caracteres');
        }
        if ($password !== $passwordConfirm) {
            json_error('Las contraseñas no coinciden');
        }

        $exists = db()->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            json_error('Este email ya está registrado', 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);
        $id = (int) db()->lastInsertId();

        $_SESSION['user_id'] = $id;
        csrf_token();

        json_response([
            'ok' => true,
            'user' => ['id' => $id, 'email' => $email],
            'csrf_token' => csrf_token(),
        ], 201);

    case 'login':
        if ($method !== 'POST') {
            json_error('Método no permitido', 405);
        }
        $body = read_json_body();
        $email = strtolower(sanitize_text($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        $stmt = db()->prepare('SELECT id, email, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_error('Email o contraseña incorrectos', 401);
        }

        $_SESSION['user_id'] = (int) $user['id'];
        csrf_token();

        json_response([
            'ok' => true,
            'user' => ['id' => (int) $user['id'], 'email' => $user['email']],
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
