<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (request_method() !== 'POST') {
    json_error('Método no permitido', 405);
}

require_auth();
require_csrf($_POST);

if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    json_error('Archivo requerido');
}

$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    json_error('Error al subir el archivo');
}

$maxBytes = 5 * 1024 * 1024;
if (($file['size'] ?? 0) > $maxBytes) {
    json_error('El archivo supera el límite de 5MB');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']) ?: '';
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'application/pdf' => 'pdf',
];

if (!isset($allowed[$mime])) {
    json_error('Tipo de archivo no permitido');
}

$ext = $allowed[$mime];
$subdir = $ext === 'pdf' ? 'docs' : 'images';
$uploadDir = root_path() . '/uploads/' . $subdir;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$name = bin2hex(random_bytes(16)) . '.' . $ext;
$dest = $uploadDir . '/' . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_error('No se pudo guardar el archivo', 500);
}

$url = 'uploads/' . $subdir . '/' . $name;
json_response(['ok' => true, 'url' => $url, 'mime' => $mime]);
