<?php
/**
 * Página de resultados del buscador del header.
 *
 * Busca PRODUCTOS por nombre y NOTICIAS por título (en el idioma activo y en
 * el otro, así "poultry" encuentra igual al producto cargado en español).
 * Devuelve JSON con ?ajax=1 para las sugerencias en vivo de assets/js/search.js.
 */
require __DIR__ . '/inc/public.php';

$q = termino_busqueda();
$productos = buscar_productos($q);
$noticias = buscar_noticias($q);
$total = count($productos) + count($noticias);

$productoDetailBase = 'product-single.php';
$noticiaDetailBase = 'blog-single.php';

// Sugerencias en vivo del buscador (dropdown): JSON acotado, sin HTML.
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    // $campo: 'nombre' en productos, 'titulo' en noticias. $etiqueta: la
    // columna que se muestra debajo del título (área de negocio en ambos).
    $mapear = static function (array $rows, string $campo, string $etiqueta, string $base): array {
        return array_map(static fn(array $r): array => [
            'id' => (int) $r['id'],
            'titulo' => campo_i18n($r, $campo),
            'categoria' => area_label((string) ($r[$etiqueta] ?? '')),
            'url' => $base . '?id=' . (int) $r['id'],
        ], $rows);
    };
    json_response([
        'q' => $q,
        'total' => $total,
        'productos' => $mapear(array_slice($productos, 0, 5), 'nombre', 'area_negocio', $productoDetailBase),
        'noticias' => $mapear(array_slice($noticias, 0, 5), 'titulo', 'categoria', $noticiaDetailBase),
    ]);
}

i18n_begin();

// Paginado: cada sección lleva su propio número de página, igual que en blog.php.
$productosPorPagina = 9;
$paginaProductos = current_page('page_productos');
$productosPagina = array_slice($productos, ($paginaProductos - 1) * $productosPorPagina, $productosPorPagina);

$noticiasPorPagina = 6;
$paginaNoticias = current_page('page_noticias');
$noticiasPagina = array_slice($noticias, ($paginaNoticias - 1) * $noticiasPorPagina, $noticiasPorPagina);
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?= e(current_lang()) ?>">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('search.hero_title')) ?> - Insalcor</title>
    <meta name="description" content="Insalcor ofrece soluciones integrales en insumos, seguridad industrial, limpieza, mantenimiento y servicios para empresas. Compromiso, calidad y atención personalizada.">
    <meta name="keywords" content="Insalcor, insumos industriales, seguridad industrial, limpieza, mantenimiento, equipos de protección personal, servicios empresariales, Uruguay">
    <meta name="author" content="Insalcor">
    <meta name="robots" content="noindex,follow">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="assets/images/favicon/favicon.ico" rel="icon"/>
    <link href="assets/css/vendor.min.css" rel="stylesheet"/>
    <link href="assets/css/style.css" rel="stylesheet"/>
    <link href="assets/css/search.css" rel="stylesheet"/>
  </head>
  
  <body data-i18n-base="assets/i18n" data-lang="es" data-api-root="." data-asset-prefix="" data-noticia-detail="blog-single.php">
    <div class="preloader">
      <div class="spinner">
        <div class="dot1"></div>
        <div class="dot2"></div>
      </div>
    </div>

    <div class="wrapper clearfix" id="wrapperParallax">
      <!-- Buscador -->
      <div class="module-content module-fullscreen module-search-box">
        <div class="pos-vertical-center">
          <div class="container">
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-8 offset-lg-2">
                <form class="form-search" action="buscar.php" method="get" role="search">
                  <input class="form-control" type="text" name="q" placeholder="Buscar" data-i18n-placeholder="common.search_placeholder"/>
                  <button type="submit" aria-label="Buscar" data-i18n-aria="common.search"></button>
                </form>
              </div>
            </div>
          </div>
        </div><a class="module-cancel" href="#"><i class="fas fa-times"></i></a>
      </div>
      
      <!--   Header   -->
      <header class="header header-light header-topbar" id="navbar-spy">
        <nav class="navbar navbar-expand-xl navbar-sticky" id="primary-menu"><a class="navbar-brand" href="index.php"><img class="logo logo-dark" src="assets/images/logo/logo-dark.png" alt="Insalcor"/><img class="logo logo-mobile" src="assets/images/logo/logo-mobile.png" alt="Medisch Logo"/></a>
          <div class="module-holder module-holder-phone">
            <!--  Search  -->
            <div class="module module-search float-left">
              <div class="module-icon search-icon"><i class="icon-search"></i></div>
            </div>
          
            <!-- Language-->
            <div class="module module-language">
              <div class="selected"><img src="assets/images/module-language/uy.png" alt=""/><span data-i18n="lang.name">Español</span><i class="fas fa-chevron-down"></i></div>
              <div class="lang-list">
                <ul>
                  <li><img src="assets/images/module-language/en.png" alt=""/><a href="<?= e(lang_switch_url('en')) ?>" data-i18n="lang.name_en">Inglés</a></li>
                  <li><img src="assets/images/module-language/uy.png" alt=""/><a href="<?= e(lang_switch_url('es')) ?>" data-i18n="lang.name_es">Español</a></li>
                </ul>
              </div>
            </div>

            <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
          </div>

          <!-- Navbar -->
          <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ">
              <li class="nav-item"><a href="#"><span data-i18n="nav.home">INICIO</span></a>
              </li>
              <li class="nav-item"><a href="./nosotros.html"><span data-i18n="nav.about">NOSOTROS</span></a>
              </li>
              <li class="nav-item has-dropdown"><a class="dropdown-toggle" href="#"
                  data-toggle="dropdown"><span data-i18n="nav.business">ÁREAS DE NEGOCIO</span></a>
                <ul class="dropdown-menu">
                  <li class="nav-item"><a href="nutricion-animal.php"><span data-i18n="nav.nutrition">Nutrición</span></a></li>
                  <li class="nav-item"><a href="pharma-vetpharma.php"><span data-i18n="nav.pharma">Pharma y VetPharma</span></a></li>
          
                </ul>
              </li>
              <li class="nav-item"><a href="blog.php"><span data-i18n="nav.news">NOVEDADES</span></a>
              </li>
              <li class="nav-item" id="contact"><a href="contact.html"><span data-i18n="nav.contact">CONTACTO</span></a></li>
            </ul>
            
            <div class="module-holder">
              <!--  Search  -->
              <div class="module module-search float-left">
                <div class="module-icon search-icon"><i class="icon-search"></i></div>
              </div>

            <!--Language-->
            <div class="module module-language">
              <div class="selected"><img src="assets/images/module-language/uy.png" alt=""/><span data-i18n="lang.name">Español</span><i class="fas fa-chevron-down"></i></div>
              <div class="lang-list">
                <ul>
                  <li><img src="assets/images/module-language/en.png" alt=""/><a href="<?= e(lang_switch_url('en')) ?>" data-i18n="lang.name_en">Inglés</a></li>
                  <li><img src="assets/images/module-language/uy.png" alt=""/><a href="<?= e(lang_switch_url('es')) ?>" data-i18n="lang.name_es">Español</a></li>
                </ul>
              </div>
            </div>
              </div>
            </div>
          </nav>
      </header>
      <!--  Page Title Section -->
      <section class="hero bg-overlay bg-overlay-dark">
        <div class="bg-section"> <img src="assets/images/heros/novedades/img1.png" alt="background"/></div>
        <div class="container">
          <div class="hero-content">
            <div class="row">
              <div class="col-12 col-lg-6">
                <h1 class="hero-title"><?= e(t('search.hero_title')) ?></h1>
                <h2 class="hero-desc"><?= e(t('search.hero_desc')) ?></h2>
              </div>
              <div class="col-12">
                <ol class="breadcrumb d-flex justify-content-center align--bottom">
                  <li class="breadcrumb-item"><a href="index.php" data-i18n="blog.breadcrumb_home">Inicio</a></li>
                  <li class="breadcrumb-item active"><a href="buscar.php"><?= e(t('search.breadcrumb')) ?></a></li>
                </ol>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!--  Resultados  -->
      <section class="blog blog-grid" id="resultados">
        <div class="container">

          <!-- Buscador de la propia página, para refinar sin reabrir el modal -->
          <div class="row">
            <div class="col-12 col-lg-8 offset-lg-2">
              <form class="form-search search-page-form" action="buscar.php" method="get" role="search">
                <input class="form-control" type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>"
                       placeholder="<?= e(t('common.search_placeholder')) ?>"
                       <?php /* Sólo se enfoca si todavía no hay búsqueda: con resultados en
                                pantalla, el autofoco saltearía el encabezado al cargar. */ ?>
                       aria-label="<?= e(t('search.submit')) ?>"<?= $q === '' ? ' autofocus' : '' ?>/>
                <button type="submit" aria-label="<?= e(t('search.submit')) ?>"><i class="fas fa-search"></i></button>
              </form>
            </div>
          </div>

          <div class="row">
            <div class="col-12 col-lg-8 offset-lg-2">
              <div class="heading heading-7 text--center search-heading">
                <?php if ($q === ''): ?>
                  <p class="search-summary"><?= e(t('search.empty_query', ['min' => BUSCADOR_MIN_CHARS])) ?></p>
                <?php else: ?>
                  <h2 class="heading-title"><?= e(t('search.results_for', ['q' => $q])) ?></h2>
                  <p class="search-summary"><?= e(t('search.count', ['n' => $total])) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if ($q !== '' && $total === 0): ?>
            <div class="row">
              <div class="col-12 text--center">
                <p><?= e(t('search.no_results', ['q' => $q])) ?></p>
                <p class="search-hint"><?= e(t('search.no_results_hint')) ?></p>
              </div>
            </div>
          <?php endif; ?>

        </div>
      </section>

      <?php if ($productos): ?>
      <!--  Productos encontrados  -->
      <section class="products products-grid pt-0" id="resultados-productos">
        <div class="container">
          <div class="row">
            <div class="col-12">
              <div class="heading heading-7">
                <h3 class="heading-title"><?= e(t('search.products_title')) ?> (<?= count($productos) ?>)</h3>
              </div>
            </div>
          </div>
          <div class="row">
            <?php foreach ($productosPagina as $item) { echo render_product_card($item, $productoDetailBase); } ?>
          </div>
          <?= render_pagination(count($productos), $productosPorPagina, $paginaProductos, 'page_productos') ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if ($noticias): ?>
      <!--  Noticias encontradas  -->
      <section class="blog blog-grid pt-0" id="resultados-noticias">
        <div class="container">
          <div class="row">
            <div class="col-12">
              <div class="heading heading-7">
                <h3 class="heading-title"><?= e(t('search.news_title')) ?> (<?= count($noticias) ?>)</h3>
              </div>
            </div>
          </div>
          <div class="row">
            <?php foreach ($noticiasPagina as $item) { echo render_noticia_card($item, $noticiaDetailBase); } ?>
          </div>
          <?= render_pagination(count($noticias), $noticiasPorPagina, $paginaNoticias, 'page_noticias') ?>
        </div>
      </section>
      <?php endif; ?>

      <!-- CTA Section -->
      <section class="cta cta-5" id="cta-5">
        <div class="bg-section"> <img src="assets/images/background/wavy-pattern.png" alt="background"/></div>
        <div class="container">
          <div class="row align-items-center mb-60">
            <div class="col-12 col-lg-5">
              <div class="heading heading-8 heading-light">
                <h2 class="heading-title" data-i18n="cta.title">¿Querés conocer más sobre nuestras soluciones?</h2>
                <p class="paragraph" data-i18n="cta.desc">Nuestro equipo técnico y comercial está listo para asesorarte en cada paso.</p>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <!--Pendiente cambiar clase video-->
              <div class="video" id="video1">
                <a class="btn btn--white btn-line" href="https://api.whatsapp.com/send/?phone=59895144852&text=Hola%20quisiera%20asesoramiento%20comercial." target="_blank"><i class="fab fa-whatsapp"></i><span data-i18n="common.contact_us">Contactanos</span></a>
              </div>
            </div>
          </div>
        </div>
      </section>

    
      <!-- Footer -->
      <footer class="footer footer-1 mt-60">
        <div class="footer-top insalcor-footer">
          <div class="container">
            <div class="row align-items-start">

              <!-- Columna izquierda: logo + secciones -->
              <div class="col-md-4 col-lg-4 mb-4 mb-lg-0">
                <div class="footer-widget footer-brand">
                  <div class="footer-logo mb-4">
                    <a href="index.php">
                      <img src="assets/images/logo/logo-white.png" alt="Insalcor">
                    </a>
                  </div>

                  <h5 class="footer-title" data-i18n="footer.sections_title">SECCIONES PRINCIPALES</h5>
                  <ul class="footer-menu">
                    <li><a href="/nosotros.html" data-i18n="footer.about">Nosotros</a></li>
                    <li><a href="/nutricion-animal.php" data-i18n="footer.nutrition">Nutrición Animal</a></li>
                    <li><a href="pharma-vetpharma.php" data-i18n="footer.pharma">Pharma</a></li>
                    <li><a href="pharma-vetpharma.php" data-i18n="footer.vetpharma">VetPharma</a></li>
                    <li><a href="/blog.php" data-i18n="footer.news">Novedades</a></li>
                    <li><a href="/contact.html" data-i18n="footer.contact">Contacto</a></li>
                  </ul>
                </div>
              </div>

              <!-- Columna centro: Nuestras oficinas -->
              <div class="col-md-8 col-lg-4 mb-4 mb-lg-0">
                <div class="footer-widget offices-widget">
                  <h6 class="footer-title" data-i18n="footer.offices_title">NUESTRAS OFICINAS</h6>

                  <!-- Uruguay -->
                  <div class="office-block">
                    <h6 class="office-country">
                      <img src="https://flagcdn.com/uy.svg" class="flag-icon" alt="Uruguay">
                      URUGUAY
                    </h6>
                    <ul class="office-list">
                      <li>
                        <i class="fas fa-map-marker-alt"></i>
                        Ruta 1 (vieja), Km. 34<br>
                        Ciudad del Plata - San José, Uruguay
                      </li>
                      <li>
                        <i class="fas fa-envelope"></i>
                        info@insalcor.com.uy
                      </li>
                      <li>
                        <i class="fas fa-phone-alt"></i>
                        (+598) 2304 2031 | 2347 7875
                      </li>
                    </ul>
                  </div>

                  <hr class="office-divider">

                  <!-- Argentina -->
                  <div class="office-block">
                    <h6 class="office-country">
                      <img src="https://flagcdn.com/ar.svg" class="flag-icon" alt="Argentina">
                      ARGENTINA
                    </h6>
                    <ul class="office-list">
                      <li>
                        <i class="fas fa-map-marker-alt"></i>
                        General Las Heras 1735<br>
                        Vicente López - Buenos Aires, Argentina
                      </li>
                      <li>
                        <i class="fas fa-envelope"></i>
                        leandro.galatro@insalcor.com
                      </li>
                      <li>
                        <i class="fas fa-phone-alt"></i>
                        (+54) 911 2727 2609
                      </li>
                    </ul>
                  </div>

                  <hr class="office-divider">

                  <!-- Paraguay -->
                  <div class="office-block">
                    <h6 class="office-country">
                      <img src="https://flagcdn.com/py.svg" class="flag-icon" alt="Paraguay">
                      PARAGUAY
                    </h6>
                    <ul class="office-list">
                      <li>
                        <i class="fas fa-map-marker-alt"></i>
                        San Francisco 457 e/España y De la Fuente<br>
                        Asunción, Paraguay
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Columna derecha: redes sociales -->
              <div class="col-md-4 col-lg-4">
                <div class="footer-widget footer-social">
                  <h5 class="footer-title" data-i18n-html="footer.social_title">SEGUÍ NUESTRAS REDES Y<br>
                    CONOCÉ LAS ÚLTIMAS<br>
                    NOVEDADES</h5>

                  <h6 class="social-title" data-i18n="footer.nutrition">Nutrición Animal</h6>
                  <ul class="footer-social-list">
                    <li>
                      <a href="https://www.instagram.com/insalcor_nutrition_/" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                      </a>
                    </li>
                    <li>
                      <a href="https://www.facebook.com/profile.php?id=61580200547237" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                      </a>
                    </li>
                  </ul>
                  
                  <h6 class="social-title mt-30" data-i18n="nav.pharma">Pharma y VetPharma</h6>
                  <ul class="footer-social-list">
                    <li>
                      <a href="https://www.instagram.com/insalcorpharma/" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                      </a>
                    </li>
                    <li>
                      <a href="https://www.linkedin.com/company/insalcorr-pharma" aria-label="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                      </a>
                    </li>
                  </ul>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Franja inferior -->
        <div class="footer-bottom insalcor-footer-bottom">
          <div class="container">
            <div class="row align-items-center">
              <div class="col-md-6">
                <p class="mb-0 footer-copy">
                  <span data-i18n="footer.copyright_prefix">Copyright © Insalcor</span> <span class="current-year"></span><span data-i18n="footer.copyright_suffix">. Todos los derechos reservados.</span>
                </p>
              </div>
              <div class="col-md-6 text-md-right mt-2 mt-md-0">
                <div class="footer-credits">
                  <a href="https://www.watt.com.uy/">Watt</a>
                  <span class="credits-divider">|</span>
                  <a href="https://www.linkedin.com/company/strawberry-web-design">Strawberry Web Design</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </footer>
            
      
      <!--Back to top btn-->
      <div class="backtop" id="back-to-top">
        <svg class="bi bi-chevron-up" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708l6-6z"></path>
        </svg>
      </div>
    </div>

    <script src="assets/js/vendor/jquery-3.6.0.min.js"></script>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/functions.js"></script>
    <script src="assets/js/i18n.js"></script>
    <script src="assets/js/search.js"></script>
</body>
</html>