<?php
/**
 * Sistema de búsqueda para Insalcor
 * VERSIÓN PARA CARPETA /php/
 * Busca en productos y noticias
 */

require_once '../includes/db.php';      // ACTUALIZADO: ../ porque estamos en /php/
require_once '../includes/lang.php';    // ACTUALIZADO: ../ porque estamos en /php/

$lang = getCurrentLang();
$conn = getDBConnection();

// Obtener término de búsqueda
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

$results = [
    'products' => [],
    'blog' => [],
    'total' => 0
];

if ($search_term && strlen($search_term) >= 3) {
    $search_like = '%' . $search_term . '%';
    
    // Buscar en productos
    $products_query = "SELECT 
        p.id,
        p.name_$lang as name,
        p.short_description_$lang as description,
        p.image,
        p.slug,
        c.name_$lang as category,
        'product' as type
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.active = 1 
    AND (
        p.name_$lang LIKE ? 
        OR p.short_description_$lang LIKE ? 
        OR p.description_$lang LIKE ?
    )
    LIMIT 10";
    
    $stmt = $conn->prepare($products_query);
    $stmt->bind_param('sss', $search_like, $search_like, $search_like);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $results['products'][] = $row;
    }
    
    // Buscar en blog
    $blog_query = "SELECT 
        b.id,
        b.title_$lang as name,
        b.excerpt_$lang as description,
        b.image,
        b.slug,
        c.name_$lang as category,
        'blog' as type
    FROM blog_posts b
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE b.active = 1 
    AND b.published_at <= NOW()
    AND (
        b.title_$lang LIKE ? 
        OR b.excerpt_$lang LIKE ? 
        OR b.content_$lang LIKE ?
    )
    LIMIT 10";
    
    $stmt = $conn->prepare($blog_query);
    $stmt->bind_param('sss', $search_like, $search_like, $search_like);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $results['blog'][] = $row;
    }
    
    $results['total'] = count($results['products']) + count($results['blog']);
}

// Si es una petición AJAX, devolver JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// Si no, mostrar página de resultados
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $lang == 'es' ? 'Resultados de búsqueda' : 'Search results'; ?> - Insalcor</title>
    <!-- ACTUALIZADO: Rutas con ../ -->
    <link href="../assets/css/vendor.min.css" rel="stylesheet"/>
    <link href="../assets/css/style.css" rel="stylesheet"/>
    <link href="../assets/css/search.css" rel="stylesheet"/>
    <style>
        .search-results {
            padding: 60px 0;
        }
        .search-header {
            margin-bottom: 40px;
        }
        .result-item {
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .result-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .result-type {
            display: inline-block;
            padding: 4px 12px;
            background: #007bff;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .result-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .result-title a {
            color: #333;
            text-decoration: none;
        }
        .result-title a:hover {
            color: #007bff;
        }
        .result-description {
            color: #666;
            margin-bottom: 10px;
        }
        .result-category {
            color: #999;
            font-size: 14px;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
        }
        .no-results h3 {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    
    <!-- Incluir header si existe -->
    <?php 
    $header_file = file_exists('../includes/header.php') ? '../includes/header.php' : null;
    if ($header_file) {
        include $header_file;
    }
    ?>
    
    <section class="search-results">
        <div class="container">
            
            <div class="search-header">
                <h1>
                    <?php echo $lang == 'es' ? 'Resultados de búsqueda' : 'Search results'; ?>
                    <?php if ($search_term): ?>
                        <?php echo $lang == 'es' ? 'para' : 'for'; ?>: 
                        <strong>"<?php echo htmlspecialchars($search_term); ?>"</strong>
                    <?php endif; ?>
                </h1>
                
                <?php if ($results['total'] > 0): ?>
                    <p><?php echo $results['total']; ?> 
                        <?php echo $lang == 'es' ? 'resultados encontrados' : 'results found'; ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <?php if ($results['total'] == 0): ?>
                <div class="no-results">
                    <h3>
                        <?php echo $lang == 'es' 
                            ? 'No se encontraron resultados' 
                            : 'No results found'; ?>
                    </h3>
                    <p>
                        <?php echo $lang == 'es' 
                            ? 'Intenta con otros términos de búsqueda' 
                            : 'Try with different search terms'; ?>
                    </p>
                    <!-- ACTUALIZADO: Rutas con ../ -->
                    <a href="<?php echo '../' . getLangUrl('products.php'); ?>" class="btn btn--primary">
                        <?php echo $lang == 'es' ? 'Ver todos los productos' : 'View all products'; ?>
                    </a>
                </div>
            <?php else: ?>
                
                <!-- Resultados de Productos -->
                <?php if (!empty($results['products'])): ?>
                    <div class="results-section">
                        <h2>
                            <?php echo $lang == 'es' ? 'Productos' : 'Products'; ?>
                            (<?php echo count($results['products']); ?>)
                        </h2>
                        
                        <?php foreach ($results['products'] as $product): ?>
                            <div class="result-item">
                                <span class="result-type">
                                    <?php echo $lang == 'es' ? 'Producto' : 'Product'; ?>
                                </span>
                                
                                <h3 class="result-title">
                                    <!-- ACTUALIZADO: Ruta con ../ -->
                                    <a href="<?php echo '../' . getLangUrl('product-single.php', $lang, ['slug' => $product['slug']]); ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                
                                <?php if ($product['description']): ?>
                                    <p class="result-description">
                                        <?php echo htmlspecialchars(substr($product['description'], 0, 200)); ?>...
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($product['category']): ?>
                                    <p class="result-category">
                                        <?php echo htmlspecialchars($product['category']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Resultados de Blog -->
                <?php if (!empty($results['blog'])): ?>
                    <div class="results-section mt-5">
                        <h2>
                            <?php echo $lang == 'es' ? 'Noticias' : 'News'; ?>
                            (<?php echo count($results['blog']); ?>)
                        </h2>
                        
                        <?php foreach ($results['blog'] as $post): ?>
                            <div class="result-item">
                                <span class="result-type" style="background: #28a745;">
                                    <?php echo $lang == 'es' ? 'Noticia' : 'News'; ?>
                                </span>
                                
                                <h3 class="result-title">
                                    <!-- ACTUALIZADO: Ruta con ../ -->
                                    <a href="<?php echo '../' . getLangUrl('blog.php', $lang, ['slug' => $post['slug']]); ?>">
                                        <?php echo htmlspecialchars($post['name']); ?>
                                    </a>
                                </h3>
                                
                                <?php if ($post['description']): ?>
                                    <p class="result-description">
                                        <?php echo htmlspecialchars(substr($post['description'], 0, 200)); ?>...
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($post['category']): ?>
                                    <p class="result-category">
                                        <?php echo htmlspecialchars($post['category']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
        </div>
    </section>
    
    <!-- Incluir footer si existe -->
    <?php 
    $footer_file = file_exists('../includes/footer.php') ? '../includes/footer.php' : null;
    if ($footer_file) {
        include $footer_file;
    }
    ?>
    
    <!-- ACTUALIZADO: Rutas con ../ -->
    <script src="../assets/js/vendor.min.js"></script>
    <script src="../assets/js/functions.js"></script>
</body>
</html>
<?php $conn->close(); ?>
