<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/bootstrap.php';

db(); // ensure schema exists

$products = [
    [
        'nombre' => 'A-Max Ultra',
        'descripcion' => '<p>A Max es un cultivo de levadura Saccharomyces cerevisiae, diseñado para alimentar tanto a rumiantes, monogastricos, como a toda clase de animales domesticos, mejorando las funciones gastrointestinales y la palatabilidad del alimento, dando como resultado una mejoreficiencia alimenticia.</p>',
        'imagen' => 'assets/images/products/grid/1.png',
        'area_negocio' => 'Nutricion Animal',
        'categoria' => 'Aditivos',
        'marca' => 'Otros',
        'especie' => 'Ganadería',
        'estado' => 'published',
    ],
    [
        'nombre' => 'A-Max Ultra',
        'descripcion' => '<p>A Max es un cultivo de levadura Saccharomyces cerevisiae, diseñado para alimentar tanto a rumiantes, monogastricos, como a toda clase de animales domesticos, mejorando las funciones gastrointestinales y la palatabilidad del alimento, dando como resultado una mejoreficiencia alimenticia.</p>',
        'imagen' => 'assets/images/products/grid/1.png',
        'area_negocio' => 'Pharma',
        'categoria' => 'Excipientes',
        'marca' => 'Mingtai Chemicals',
        'especie' => null,
        'estado' => 'published',
    ],
    [
        'nombre' => 'A-Max Ultra',
        'descripcion' => '<p>A Max es un cultivo de levadura Saccharomyces cerevisiae, diseñado para alimentar tanto a rumiantes, monogastricos, como a toda clase de animales domesticos, mejorando las funciones gastrointestinales y la palatabilidad del alimento, dando como resultado una mejoreficiencia alimenticia.</p>',
        'imagen' => 'assets/images/products/grid/1.png',
        'area_negocio' => 'VetPharma',
        'categoria' => 'APIS',
        'marca' => 'Kerry BioScience',
        'especie' => null,
        'estado' => 'published',
    ],
    [
        'nombre' => 'Insalmix Preparto Anionica',
        'descripcion' => '<p>Insalmix Preparto Aniónica — premix nutricional para el período de preparto.</p>',
        'imagen' => 'assets/images/products/grid/1.png',
        'area_negocio' => 'Pharma',
        'categoria' => 'Premezclas',
        'marca' => 'Otros',
        'especie' => null,
        'estado' => 'published',
    ],
];

$check = db()->prepare('SELECT id FROM productos WHERE nombre = ? AND area_negocio = ? LIMIT 1');
$insert = db()->prepare(
    'INSERT INTO productos (nombre, descripcion, imagen, area_negocio, categoria, marca, especie, estado, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$added = 0;
$skipped = 0;
foreach ($products as $p) {
    $check->execute([$p['nombre'], $p['area_negocio']]);
    if ($check->fetch()) {
        $skipped++;
        continue;
    }
    $insert->execute([
        $p['nombre'],
        $p['descripcion'],
        $p['imagen'],
        $p['area_negocio'],
        $p['categoria'],
        $p['marca'],
        $p['especie'],
        $p['estado'],
        now_sql(),
    ]);
    $added++;
}

$noticias = [
    [
        'titulo' => 'Nuevo programa de capacitación lanzado',
        'extracto' => 'Iniciamos un programa de formación técnica para clientes y partners del sector nutricional.',
        'contenido' => '<p>Iniciamos un programa de formación técnica para clientes y partners del sector nutricional, con foco en calidad e innovación.</p>',
        'imagen' => 'assets/images/blog/grid/1.jpg',
        'categoria' => 'Nutrición Animal',
        'estado' => 'published',
    ],
    [
        'titulo' => 'Encuentro anual de miembros',
        'extracto' => 'Compartimos avances, proyectos y oportunidades de colaboración en el encuentro anual.',
        'contenido' => '<p>Compartimos avances, proyectos y oportunidades de colaboración en el encuentro anual de Insalcor.</p>',
        'imagen' => 'assets/images/blog/grid/1.jpg',
        'categoria' => 'Institucional',
        'estado' => 'published',
    ],
    [
        'titulo' => 'Convocatoria para voluntarios',
        'extracto' => 'Abrimos una convocatoria para quienes quieran sumarse a iniciativas de la comunidad.',
        'contenido' => '<p>Abrimos una convocatoria para quienes quieran sumarse a iniciativas de la comunidad Insalcor.</p>',
        'imagen' => 'assets/images/blog/grid/1.jpg',
        'categoria' => 'Comunidad',
        'estado' => 'draft',
    ],
];

$checkN = db()->prepare('SELECT id FROM noticias WHERE titulo = ? LIMIT 1');
$insertN = db()->prepare(
    'INSERT INTO noticias (titulo, extracto, contenido, imagen, categoria, estado, published_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

$addedN = 0;
$skippedN = 0;
foreach ($noticias as $n) {
    $checkN->execute([$n['titulo']]);
    if ($checkN->fetch()) {
        $skippedN++;
        continue;
    }
    $publishedAt = $n['estado'] === 'published' ? now_sql() : null;
    $insertN->execute([
        $n['titulo'],
        $n['extracto'],
        $n['contenido'],
        $n['imagen'],
        $n['categoria'],
        $n['estado'],
        $publishedAt,
        now_sql(),
    ]);
    $addedN++;
}

$msg = "Seed complete. Products added: {$added}, skipped: {$skipped}. Noticias added: {$addedN}, skipped: {$skippedN}.\n";
if (PHP_SAPI === 'cli') {
    echo $msg;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
}
