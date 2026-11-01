<?php
/**
 * Consultas del catálogo Pharma / VetPharma / Suplementos que llegan por el
 * formulario de contacto (assets/php/contact.php).
 *
 * El formulario es el mismo de contact.html; desde pharma-vetpharma.php se abre
 * en un modal con dos campos ocultos:
 *   contact-producto  id del producto por el que se consulta (opcional);
 *   contact-area      área desde la que se consulta (Pharma, VetPharma...).
 * Si alguno identifica una consulta Pharma, el email va a la casilla de
 * Pharma (CONTACT_TO_EMAIL_PHARMA, por defecto PHARMA_EMAIL) en vez de a la
 * general, y lleva el producto en el asunto y en el cuerpo.
 *
 * El producto se busca en la base por id: nunca se confía en un nombre que
 * venga del navegador.
 */
declare(strict_types=1);

/**
 * @return array{to: string, toName: string, asunto: string, filas: array<string, string>}|null
 *         null = no es una consulta Pharma (va a la casilla general).
 */
function consulta_pharma(array $post): ?array
{
    $productoId = (int) ($post['contact-producto'] ?? 0);
    $area = trim((string) ($post['contact-area'] ?? ''));
    if ($productoId <= 0 && $area === '') {
        return null;
    }

    // Recién acá hace falta la base (y las constantes del catálogo): el
    // formulario general no depende de que la base esté disponible.
    require_once dirname(__DIR__) . '/api/bootstrap.php';

    $producto = null;
    if ($productoId > 0) {
        try {
            $stmt = db()->prepare(
                "SELECT nombre, nombre_comercial, representada, area_negocio, categoria, subgrupo
                 FROM productos WHERE id = ? AND estado = 'published'"
            );
            $stmt->execute([$productoId]);
            $producto = $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            // Sin base no se pierde la consulta: sale igual, sin el producto.
            error_log('[contact] No se pudo leer el producto ' . $productoId . ': ' . $e->getMessage());
        }
        if ($producto && !in_array($producto['area_negocio'], AREAS_PHARMA, true)) {
            $producto = null; // un id de Nutrición no la convierte en consulta Pharma
        }
    }

    if ($producto) {
        $area = $producto['area_negocio'];
    } elseif (!in_array($area, AREAS_PHARMA, true)) {
        return null;
    }

    $filas = ['Área' => $area];
    $asunto = "Consulta $area desde el sitio web";
    if ($producto) {
        $nombre = $producto['nombre'] . ($producto['nombre_comercial'] !== '' ? ' (' . $producto['nombre_comercial'] . ')' : '');
        $asunto = "Consulta por producto $area: $nombre";
        $filas = [
            'Producto' => $nombre,
            'Área' => $area,
            'Categoría' => implode(' › ', array_filter([$producto['categoria'], $producto['subgrupo']])),
            'Representada' => $producto['representada'] ?: '—',
        ];
    }

    return [
        'to' => env('CONTACT_TO_EMAIL_PHARMA', PHARMA_EMAIL),
        'toName' => env('CONTACT_TO_NAME_PHARMA', 'Insalcor Pharma'),
        'asunto' => $asunto,
        'filas' => $filas,
    ];
}
