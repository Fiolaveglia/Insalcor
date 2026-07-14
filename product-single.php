<?php
require __DIR__ . '/inc/public.php';
i18n_begin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = $id > 0 ? pub_producto($id) : null;
$contactHref = 'contact.html';

$area = $item['area_negocio'] ?? '';
if ($area === 'Nutricion Animal') {
    $areaHref = 'nutricion-animal.php';
    $heroBg = 'assets/images/heros/nutricion-animal/Photo-1.png';
} else {
    $areaHref = 'pharma-vetpharma.php';
    $heroBg = 'assets/images/heros/pharma/img1.png';
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?= e(current_lang()) ?>">
  <head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="author" content="Insalcor"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="description" content="Insalcor - <?= e($item['nombre'] ?? 'Producto') ?>"/>
    <title>Insalcor – <?= e($item['nombre'] ?? t('common.product_not_found')) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="assets/images/favicon/favicon.ico" rel="icon"/>
    <link rel="preconnect" href="https://fonts.gstatic.com"/>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&amp;family=Roboto:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&amp;family=Rubik:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&amp;display=swap" rel="stylesheet"/>
    <link href="assets/css/vendor.min.css" rel="stylesheet"/>
    <link href="assets/css/style.css" rel="stylesheet"/>
    <link href="assets/css/search.css" rel="stylesheet"/>
    <link href="assets/css/content.css" rel="stylesheet"/>
  </head>

  <body data-i18n-base="assets/i18n" data-lang="es" data-api-root="." data-asset-prefix="">
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
                <form class="form-search">
                  <input class="form-control" type="text" placeholder="Buscar"/>
                  <button></button>
                </form>
              </div>
            </div>
          </div>
        </div><a class="module-cancel" href="#"><i class="fas fa-times"></i></a>
      </div>

      <!--   Header   -->
      <header class="header header-light header-topbar" id="navbar-spy">
        <nav class="navbar navbar-expand-xl navbar-sticky" id="primary-menu"><a class="navbar-brand" href="index.html"><img class="logo logo-dark" src="assets/images/logo/logo-dark.png" alt="Insalcor"/><img class="logo logo-mobile" src="assets/images/logo/logo-mobile.png" alt="Medisch Logo"/></a>
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
                  <li><img src="assets/images/module-language/en.png" alt=""/><a href="?lang=en" data-i18n="lang.name_en">Inglés</a></li>
                  <li><img src="assets/images/module-language/uy.png" alt=""/><a href="?lang=es" data-i18n="lang.name_es">Español</a></li>
                </ul>
              </div>
            </div>

            <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
          </div>

          <!-- Navbar -->
          <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ">
              <li class="nav-item"><a href="index.html"><span data-i18n="nav.home">INICIO</span></a>
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
                  <li><img src="assets/images/module-language/en.png" alt=""/><a href="?lang=en" data-i18n="lang.name_en">Inglés</a></li>
                  <li><img src="assets/images/module-language/uy.png" alt=""/><a href="?lang=es" data-i18n="lang.name_es">Español</a></li>
                </ul>
              </div>
            </div>
              </div>
            </div>
          </nav>
      </header>

      <!--  Page Title Section -->
      <section class="hero bg-overlay bg-overlay-dark">
        <div class="bg-section"> <img src="<?= e($heroBg) ?>" alt="background"/></div>
        <div class="container">
          <div class="hero-content">
            <div class="row">
              <div class="col-12 col-lg-8">
                <h1 class="hero-title"><?= e($item['nombre'] ?? t('common.product_not_found')) ?></h1>
                <?php if ($item): ?><h2 class="hero-desc"><?= e($item['area_negocio']) ?></h2><?php endif; ?>
              </div>
              <div class="col-12">
                <ol class="breadcrumb d-flex justify-content-center align--bottom">
                  <li class="breadcrumb-item"><a href="index.html" data-i18n="blog.breadcrumb_home">Inicio</a></li>
                  <?php if ($item): ?>
                  <li class="breadcrumb-item"><a href="<?= e($areaHref) ?>"><?= e($item['area_negocio']) ?></a></li>
                  <li class="breadcrumb-item active"><a href="javascript:void(0)"><?= e($item['nombre']) ?></a></li>
                  <?php endif; ?>
                </ol>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!--  Product  -->
      <section class="single-product" id="single-product" style="padding:60px 0">
        <div class="container">
          <?php if ($item):
              $img = asset($item['imagen']) ?: asset('assets/images/products/full/1.png');
              $ficha = $item['ficha_tecnica'] ? asset($item['ficha_tecnica']) : '';
          ?>
          <div class="row">
            <div class="col-12 col-lg-6">
              <div class="product-img">
                <img class="img-fluid" src="<?= e($img) ?>" alt="<?= e($item['nombre']) ?>"/>
                <a class="img-popup" href="<?= e($img) ?>"></a>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <div class="product-content">
                <div class="product-title"><h3><?= e($item['nombre']) ?></h3></div>
                <div class="product-category"><span><?= e(t('common.category')) ?>: <?= e($item['categoria'] ?: '—') ?></span></div>
                <div class="product-desc rich-content"><?= $item['descripcion'] ?></div>
                <div class="product-action">
                  <?php if ($ficha): ?>
                    <a class="btn btn--secondary btn-radius-right" href="<?= e($ficha) ?>" target="_blank" rel="noopener"><?= e(t('common.tech_sheet')) ?></a>
                  <?php else: ?>
                    <a class="btn btn--secondary btn-radius-right" href="#"><?= e(t('common.tech_sheet')) ?></a>
                  <?php endif; ?>
                  <a class="btn btn--secondary btn-radius-right" href="<?= e($contactHref) ?>"><?= e(t('common.inquire_product')) ?></a>
                </div>
              </div>
            </div>
          </div>
          <?php else: ?>
          <p class="text-center"><?= e(t('common.product_not_found')) ?></p>
          <?php endif; ?>
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
                    <a href="index.html">
                      <img src="assets/images/logo/logo-white.png" alt="Insalcor">
                    </a>
                  </div>

                  <h5 class="footer-title">SECCIONES PRINCIPALES</h5>
                  <ul class="footer-menu">
                    <li><a href="/nosotros.html">Nosotros</a></li>
                    <li><a href="/nutricion-animal.php">Nutrición Animal</a></li>
                    <li><a href="pharma-vetpharma.php">Pharma</a></li>
                    <li><a href="pharma-vetpharma.php">VetPharma</a></li>
                    <li><a href="/blog.php">Novedades</a></li>
                    <li><a href="/contact.html">Contacto</a></li>
                  </ul>
                </div>
              </div>

              <!-- Columna centro: Nuestras oficinas -->
              <div class="col-md-8 col-lg-4 mb-4 mb-lg-0">
                <div class="footer-widget offices-widget">
                  <h6 class="footer-title">NUESTRAS OFICINAS</h6>

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
                  <h5 class="footer-title">
                    SEGUÍ NUESTRAS REDES Y<br>
                    CONOCÉ LAS ÚLTIMAS<br>
                    NOVEDADES
                  </h5>

                  <h6 class="social-title">Nutrición Animal</h6>
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

                  <h6 class="social-title mt-30">Pharma y VetPharma</h6>
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
                  Copyright © Insalcor <span class="current-year"></span>. Todos los derechos reservados.
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
    <script src="assets/js/search.js"></script>
</body>
</html>
