<?php
/**
 * Variables de entorno.
 *
 * Primero se usa lo que venga del entorno real (variables del servidor,
 * SetEnv en Apache, panel del hosting...). Si no está definida, se busca en
 * el archivo .env de la raíz del proyecto (no se sube a git; ver .env.example).
 */
declare(strict_types=1);

function env(string $key, ?string $default = null): ?string
{
    static $file = null;

    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string) $_SERVER[$key];
    }

    if ($file === null) {
        $file = load_env_file(dirname(__DIR__) . '/.env');
    }

    return ($file[$key] ?? '') !== '' ? $file[$key] : $default;
}

/**
 * Lee un archivo KEY=valor sencillo. Ignora líneas vacías y comentarios (#),
 * y quita comillas simples o dobles alrededor del valor.
 *
 * @return array<string, string>
 */
function load_env_file(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }

    $vars = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (preg_match('/^(["\'])(.*)\1$/', $value, $m)) {
            $value = $m[2];
        }
        $vars[$key] = $value;
    }
    return $vars;
}
