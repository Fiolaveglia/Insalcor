<?php
require __DIR__ . '/inc/public.php';
i18n_begin();

$detailBase = 'product-single.php';
$area = 'Nutricion Animal';
$filters = active_filters();
$productos = pub_productos($area, $filters);
$recent = array_slice($productos, 0, 3);
$especieOptions = array_combine(ESPECIES, ESPECIES);
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?= e(current_lang()) ?>">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nutrición Animal – Insalcor</title>
    <meta name="description" content="Insalcor ofrece soluciones integrales en insumos, seguridad industrial, limpieza, mantenimiento y servicios para empresas. Compromiso, calidad y atención personalizada.">
    <meta name="keywords" content="Insalcor, insumos industriales, seguridad industrial, limpieza, mantenimiento, equipos de protección personal, servicios empresariales, Uruguay">
    <meta name="author" content="Insalcor">
    <link href="./assets/images/favicon/favicon.ico" rel="icon"/>
    <link rel="preconnect" href="https://fonts.gstatic.com"/>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&amp;family=Roboto:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&amp;family=Rubik:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&amp;display=swap" rel="stylesheet"/>
    <link href="assets/css/vendor.min.css" rel="stylesheet"/>
    <link href="assets/css/style.css" rel="stylesheet"/>
    <link href="assets/css/search.css" rel="stylesheet"/>
  </head>
  <body data-i18n-base="assets/i18n" data-lang="es" data-api-root="." data-asset-prefix="" data-product-detail="product-single.php">
    <div class="preloader">
      <div class="spinner">
        <div class="dot1"></div>
        <div class="dot2"></div>
      </div>
    </div>
    <div class="wrapper clearfix" id="wrapperParallax">
      <!-- Buscador      -->
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
              <div class="module-icon search-icon"><i class="icon-search" ></i></div>
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
            <ul class="navbar-nav">
              <li class="nav-item" ><a href="index.html"><span data-i18n="nav.home">INICIO</span></a>
              </li>
              <li class="nav-item" ><a href="nosotros.html"><span data-i18n="nav.about">NOSOTROS</span></a>
              </li>
              <li class="nav-item has-dropdown active" ><a class="dropdown-toggle" href="#"
                  data-toggle="dropdown"><span data-i18n="nav.business">ÁREAS DE NEGOCIO</span></a>
                <ul class="dropdown-menu">
                  <li class="nav-item"><a href="nutricion-animal.php"><span data-i18n="nav.nutrition">Nutrición</span></a></li>
                  <li class="nav-item"><a href="pharma-vetpharma.php"><span data-i18n="nav.pharma">Pharma y VetPharma</span></a></li>
          
                </ul>
              </li>
              <li class="nav-item" ><a href="blog.php"><span data-i18n="nav.news">NOVEDADES</span></a>
              </li>
               <!-- <li class="nav-item active"><a href="./tutoriales.html"><span data-i18n="nav.tutorials">TUTORIALES</span></a>
              </li> -->
              <li class="nav-item" id="contact" ><a href="contact.html"><span data-i18n="nav.contact">CONTACTO</span></a></li>
            </ul>
            
            <div class="module-holder">
              <!--  Search  -->
              <div class="module module-search float-left">
                <div class="module-icon search-icon"><i class="icon-search" ></i></div>
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

    <!-- Hero Slider   -->
      <section class="slider slider-3" id="slider-3">
        <div class="container-fluid pr-0 pl-0">
          <div class="slider-carousel owl-carousel carousel-navs carousel-dots" data-slide="1" data-slide-rs="1" data-autoplay="true" data-nav="true" data-dots="true" data-space="0" data-loop="true" data-speed="800" data-slider-id="#custom-carousel">
            <div class="slide bg-overlay bg-overlay-dark-slider">
              <div class="bg-section"><img src="assets/images/heros/nutricion-animal/Photo-1.png" alt="Background"/></div>
              <div class="container">
                <div class="slide-content">
                  <div class="row">
                    <div class="col-12 col-lg-7">
                      <h1 class="slide-headline" data-i18n="nutrition.hero_title">Nutrición Animal</h1>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-12 col-lg-6">
                      <p class="slide-desc">Ofrecemos una línea completa de productos nutricionales desarrollados para cada especie animal, con respaldo técnico, calidad garantizada y asesoramiento especializado.</p>
                      <div class="slide-action">
                        <a class="btn btn--white btn-line btn-line-after btn-line-inversed" href="contact.html"> <span>Contáctanos</span><span class="line"> <span></span></span></a>
                        <a class="btn btn--transparent btn-line btn-line-after btn-line-inversed" href="nosotros.html"> <span>Sobre Nosotros</span><span class="line"> <span></span></span></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="slide bg-overlay bg-overlay-dark-slider">
              <div class="bg-section"><img src="assets/images/heros/nutricion-animal/Photo-2.png" alt="Background"/></div>
              <div class="container">
                <div class="slide-content">
                  <div class="row">
                    <div class="col-12 col-lg-7">
                      <h1 class="slide-headline" data-i18n="nutrition.hero_title">Nutrición Animal</h1>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-12 col-lg-6">
                      <p class="slide-desc">Ofrecemos una línea completa de productos nutricionales desarrollados para cada especie animal, con respaldo técnico, calidad garantizada y asesoramiento especializado.</p>
                      <div class="slide-action">
                        <a class="btn btn--white btn-line btn-line-after btn-line-inversed" href="contact.html"> <span>Contáctanos</span><span class="line"> <span></span></span></a>
                        <a class="btn btn--transparent btn-line btn-line-after btn-line-inversed" href="nosotros.html"> <span>Sobre Nosotros</span><span class="line"> <span></span></span></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="slide bg-overlay bg-overlay-dark-slider">
              <div class="bg-section"><img src="assets/images/heros/nutricion-animal/Photo-3.png" alt="Background"/></div>
              <div class="container">
                <div class="slide-content">
                  <div class="row">
                    <div class="col-12 col-lg-7">
                      <h1 class="slide-headline" data-i18n="nutrition.hero_title">Nutrición Animal</h1>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-12 col-lg-6">
                      <p class="slide-desc">Ofrecemos una línea completa de productos nutricionales desarrollados para cada especie animal, con respaldo técnico, calidad garantizada y asesoramiento especializado.</p>
                      <div class="slide-action">
                        <a class="btn btn--white btn-line btn-line-after btn-line-inversed" href="contact.html"> <span>Contáctanos</span><span class="line"> <span></span></span></a>
                        <a class="btn btn--transparent btn-line btn-line-after btn-line-inversed" href="nosotros.html"> <span>Sobre Nosotros</span><span class="line"> <span></span></span></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="slide bg-overlay bg-overlay-dark-slider">
              <div class="bg-section"><img src="assets/images/heros/nutricion-animal/Photo-4.png" alt="Background"/></div>
              <div class="container">
                <div class="slide-content">
                  <div class="row">
                    <div class="col-12 col-lg-7">
                      <h1 class="slide-headline" data-i18n="nutrition.hero_title">Nutrición Animal</h1>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-12 col-lg-6">
                      <p class="slide-desc">Ofrecemos una línea completa de productos nutricionales desarrollados para cada especie animal, con respaldo técnico, calidad garantizada y asesoramiento especializado.</p>
                      <div class="slide-action">
                        <a class="btn btn--white btn-line btn-line-after btn-line-inversed" href="contact.html"> <span>Contáctanos</span><span class="line"> <span></span></span></a>
                        <a class="btn btn--transparent btn-line btn-line-after btn-line-inversed" href="nosotros.html"> <span>Sobre Nosotros</span><span class="line"> <span></span></span></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="slide bg-overlay bg-overlay-dark-slider">
              <div class="bg-section"><img src="assets/images/heros/nutricion-animal/Photo-5.png" alt="Background"/></div>
              <div class="container">
                <div class="slide-content">
                  <div class="row">
                    <div class="col-12 col-lg-7">
                      <h1 class="slide-headline" data-i18n="nutrition.hero_title">Nutrición Animal</h1>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-12 col-lg-6">
                      <p class="slide-desc">Ofrecemos una línea completa de productos nutricionales desarrollados para cada especie animal, con respaldo técnico, calidad garantizada y asesoramiento especializado.</p>
                      <div class="slide-action">
                        <a class="btn btn--white btn-line btn-line-after btn-line-inversed" href="contact.html"> <span>Contáctanos</span><span class="line"> <span></span></span></a>
                        <a class="btn btn--transparent btn-line btn-line-after btn-line-inversed" href="nosotros.html"> <span>Sobre Nosotros</span><span class="line"> <span></span></span></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="slide bg-overlay bg-overlay-dark-slider">
              <div class="bg-section"><img src="assets/images/heros/nutricion-animal/Photo-6.png" alt="Background"/></div>
              <div class="container">
                <div class="slide-content">
                  <div class="row">
                    <div class="col-12 col-lg-7">
                      <h1 class="slide-headline" data-i18n="nutrition.hero_title">Nutrición Animal</h1>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-12 col-lg-6">
                      <p class="slide-desc">Ofrecemos una línea completa de productos nutricionales desarrollados para cada especie animal, con respaldo técnico, calidad garantizada y asesoramiento especializado.</p>
                      <div class="slide-action">
                        <a class="btn btn--white btn-line btn-line-after btn-line-inversed" href="contact.html"> <span>Contáctanos</span><span class="line"> <span></span></span></a>
                        <a class="btn btn--transparent btn-line btn-line-after btn-line-inversed" href="nosotros.html"> <span>Sobre Nosotros</span><span class="line"> <span></span></span></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="slide bg-overlay bg-overlay-dark-slider">
              <div class="bg-section"><img src="assets/images/heros/nutricion-animal/Photo-7.png" alt="Background"/></div>
              <div class="container">
                <div class="slide-content">
                  <div class="row">
                    <div class="col-12 col-lg-7">
                      <h1 class="slide-headline" data-i18n="nutrition.hero_title">Nutrición Animal</h1>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-12 col-lg-6">
                      <p class="slide-desc">Ofrecemos una línea completa de productos nutricionales desarrollados para cada especie animal, con respaldo técnico, calidad garantizada y asesoramiento especializado.</p>
                      <div class="slide-action">
                        <a class="btn btn--white btn-line btn-line-after btn-line-inversed" href="contact.html"> <span>Contáctanos</span><span class="line"> <span></span></span></a>
                        <a class="btn btn--transparent btn-line btn-line-after btn-line-inversed" href="nosotros.html"> <span>Sobre Nosotros</span><span class="line"> <span></span></span></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="slide bg-overlay bg-overlay-dark-slider">
              <div class="bg-section"><img src="assets/images/heros/nutricion-animal/Photo-8.png" alt="Background"/></div>
              <div class="container">
                <div class="slide-content">
                  <div class="row">
                    <div class="col-12 col-lg-7">
                      <h1 class="slide-headline" data-i18n="nutrition.hero_title">Nutrición Animal</h1>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-12 col-lg-6">
                      <p class="slide-desc">Ofrecemos una línea completa de productos nutricionales desarrollados para cada especie animal, con respaldo técnico, calidad garantizada y asesoramiento especializado.</p>
                      <div class="slide-action">
                        <a class="btn btn--white btn-line btn-line-after btn-line-inversed" href="contact.html"> <span>Contáctanos</span><span class="line"> <span></span></span></a>
                        <a class="btn btn--transparent btn-line btn-line-after btn-line-inversed" href="nosotros.html"> <span>Sobre Nosotros</span><span class="line"> <span></span></span></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!--Products Section-->
      <section class="shop" id="shop">
        <div class="container">
          <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-6 offset-lg-3">
              <div class="heading heading-7 text--center">
                <h2 class="heading-title" data-i18n="shop.our_products">Nuestros Productos</h2>
              </div>
            </div>
          </div>

          <div class="row">
            <!--  Sidebar Products  -->
            <div class="col-12 col-lg-3">
              <div class="sidebar sidebar-shop">

                <!-- Search-->
                <div class="widget widget-search">
                  <div class="widget-title">
                    <h5 data-i18n="shop.search">Buscar</h5>
                  </div>
                  <div class="widget-content">
                    <form class="form-search" method="get" action="">
                      <?php if (current_lang() !== 'es'): ?><input type="hidden" name="lang" value="<?= e(current_lang()) ?>"/><?php endif; ?>
                      <div class="input-group">
                        <input class="form-control" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar ..." data-i18n-placeholder="common.search_placeholder"/><span class="input-group-btn">
                          <button class="btn" type="submit"><i class="icon-search"></i></button></span>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- Especies -->
                <div class="widget especie">
                  <div class="widget-title">
                    <h5 data-i18n="shop.species">Especies</h5>
                  </div>
                  <div class="widget-content">
                    <?= render_filter_list($area, 'especie', $especieOptions) ?>
                  </div>
                </div>

                <!-- Recent Products-->
                <div class="widget widget-recent-products">
                  <div class="widget-title">
                    <h5 data-i18n="shop.recent">Productos Recientes</h5>
                  </div>
                  <div class="widget-content">
                    <?php foreach ($recent as $item) { echo render_recent_product($item, $detailBase); } ?>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- All Products -->
            <div class="col-12 col-lg-9">
              <div class="row">
                <div class="col-12">
                  <div class="shop-options">
                    <div class="products-show"><p><?= e(t('common.showing_products', ['n' => count($productos)])) ?></p></div>
                    
                    <!-- <div class="products-sort">
                      <div class="select-holder">
                        <select>
                          <option selected="" value="Default">Recientes</option>
                          <option value="Larger">Newest Items</option>
                          <option value="Larger">oldest Items</option>
                          <option value="Larger">Hot Items</option>
                          <option value="Small">Highest Price</option>
                          <option value="Medium">Lowest Price</option>
                        </select>
                      </div>
                    </div> -->
                    
                </div>
              </div>
              <div class="row">
                <?php if ($productos) {
                    foreach ($productos as $item) { echo render_product_card($item, $detailBase); }
                } else { ?>
                  <div class="col-12"><p><?= e(t('common.no_products')) ?></p></div>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>
        </div>
      </section>

      <!-- Brands-->
      <section class="cta cta-2" id="cta-2">
        <div class="container">
          <div class="heading heading-6">
            <div class="row">
               <h2 class="heading-title">Marcas que representamos</h2>
            </div>
            <!-- <div class="row">
              <div class="col-12 col-lg-5">
                <h2 class="heading-title">Marcas que representamos</h2>
              </div>
            </div> -->
          </div>
          <!-- PENDIENTE A ACTUALIZAR LAS MARCAS -->
          <div class="carousel owl-carousel carousel-continuo" data-slide="6" data-slide-rs="2" data-autoplay="true" data-nav="false" data-dots="false" data-space="30" data-loop="true" data-speed="600">
            <div class="alliance"><img class="img-fluid" src="assets/images/alliances/1.png" alt="Alianzas"/></div>
            <div class="alliance"><img class="img-fluid" src="assets/images/alliances/2.png" alt="Alianzas"/></div>
            <div class="alliance"><img class="img-fluid" src="assets/images/alliances/3.png" alt="Alianzas"/></div>
            <div class="alliance"><img class="img-fluid" src="assets/images/alliances/4.png" alt="Alianzas"/></div>
            <div class="alliance"><img class="img-fluid" src="assets/images/alliances/5.png" alt="Alianzas"/></div>
            <div class="alliance"><img class="img-fluid" src="assets/images/alliances/6.png" alt="Alianzas"/></div>
            <div class="alliance"><img class="img-fluid" src="assets/images/alliances/7.png" alt="Alianzas"/></div>
            <div class="alliance"><img class="img-fluid" src="assets/images/alliances/8.png" alt="Alianzas"/></div>
            <div class="alliance"><img class="img-fluid" src="assets/images/alliances/9.png" alt="Alianzas"/></div>
 
          </div>
        </div>
        <!-- End .container-->
      </section>



       <!--   News    -->
      <section class="blog grey-light-bg blog-grid blog-grid-3" id="blog-2">
        <div class="container">
          <div class="row"> 
            <div class="col-12 col-lg-6 offset-lg-3">
              <div class="heading heading-7 text-center">
                <h2 class="heading-title">Artículos y Novedades Recientes sobre <span class="color-segment">Nutrición Animal</span></h2>
              </div>
            </div>
          </div>
          <div class="carousel owl-carousel carousel-dots" data-slide="3" data-slide-rs="2" data-autoplay="true" data-nav="false" data-dots="true" data-space="30" data-loop="true" data-speed="200">
            <div>
              <div class="blog-entry" data-hover="">
                <div class="entry-img">
                  <div class="entry-date">
                    <div class="entry-content"><span class="day">20</span><span class="month">Ene</span><span class="year">2025</span></div>
                  </div>
                   <a href="blog-single-sidebar.html"><img src="assets/images/blog/grid/1.jpg" alt="6 tips to protect your mental health when sick"/></a>
                </div>
                <div class="entry-content">
                  <div class="entry-meta">
                    <div class="entry-category"><a href="javascript:void(0)">Nutrición Animal</a>
                    </div>
                    <!-- <div class="divider"></div>
                    <div class="entry-author"> 
                      <p>Lorem Ipsum</p>
                    </div> -->
                  </div>
                  <div class="entry-title">
                    <h4><a href="blog-single-sidebar.html">Título más extenso de la noticia que ocupa más de una fila</a></h4>
                  </div>
                  <div class="entry-bio">
                    <p>Lorem ipsum dolor sit amet consectetur. Turpis auctor pulvinar fringilla aliquet id. Mi est malesuada eu mattis. Elementum risus convallis pulvinar velit nulla mi rutrum. Id at tristique condimentum viverra vel nec vestibulum.</p>
                  </div>
                  <div class="entry-more"> <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="blog-single-sidebar.html"> 
                      <div class="line"> <span> </span></div><span>Ver Más</span></a></div>
                </div>
              </div>
              <!-- End .Card-->
           
          </div>
           <div>
              <div class="blog-entry" data-hover="">
                <div class="entry-img">
                  <div class="entry-date">
                    <div class="entry-content"><span class="day">20</span><span class="month">Ene</span><span class="year">2025</span></div>
                  </div>
                   <a href="blog-single-sidebar.html"><img src="assets/images/blog/grid/1.jpg" alt="6 tips to protect your mental health when sick"/></a>
                </div>
                <div class="entry-content">
                  <div class="entry-meta">
                    <div class="entry-category"><a href="javascript:void(0)">Nutrición Animal</a>
                    </div>
                  </div>
                  <div class="entry-title">
                    <h4><a href="blog-single-sidebar.html">Título de la noticia</a></h4>
                  </div>
                  <div class="entry-bio">
                    <p>Lorem ipsum dolor sit amet consectetur. Turpis auctor pulvinar fringilla aliquet id. Mi est malesuada eu mattis. Elementum risus convallis pulvinar velit nulla mi rutrum. Id at tristique condimentum viverra vel nec vestibulum.</p>
                  </div>
                  <div class="entry-more"> <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="blog-single-sidebar.html"> 
                      <div class="line"> <span> </span></div><span>Ver Más</span></a></div>
                </div>
              </div>
              <!-- End .Card-->
           
          </div>
           <div>
              <div class="blog-entry" data-hover="">
                <div class="entry-img">
                  <div class="entry-date">
                    <div class="entry-content"><span class="day">20</span><span class="month">Ene</span><span class="year">2025</span></div>
                  </div>
                   <a href="blog-single-sidebar.html"><img src="assets/images/blog/grid/1.jpg" alt="6 tips to protect your mental health when sick"/></a>
                </div>
                <div class="entry-content">
                  <div class="entry-meta">
                    <div class="entry-category"><a href="javascript:void(0)">Nutrición Animal</a>
                    </div>
                  </div>
                  <div class="entry-title">
                    <h4><a href="blog-single-sidebar.html">Título de la noticia</a></h4>
                  </div>
                  <div class="entry-bio">
                    <p>Lorem ipsum dolor sit amet consectetur. Turpis auctor pulvinar fringilla aliquet id. Mi est malesuada eu mattis. Elementum risus convallis pulvinar velit nulla mi rutrum. Id at tristique condimentum viverra vel nec vestibulum.</p>
                  </div>
                  <div class="entry-more"> <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="blog-single-sidebar.html"> 
                      <div class="line"> <span> </span></div><span>Ver Más</span></a></div>
                </div>
              </div>
              <!-- End .Card-->
           
          </div>
           <div>
              <div class="blog-entry" data-hover="">
                <div class="entry-img">
                  <div class="entry-date">
                    <div class="entry-content"><span class="day">20</span><span class="month">Ene</span><span class="year">2025</span></div>
                  </div>
                   <a href="blog-single-sidebar.html"><img src="assets/images/blog/grid/1.jpg" alt="6 tips to protect your mental health when sick"/></a>
                </div>
                <div class="entry-content">
                  <div class="entry-meta">
                    <div class="entry-category"><a href="javascript:void(0)">Nutrición Animal</a>
                    </div>
                  </div>
                  <div class="entry-title">
                    <h4><a href="blog-single-sidebar.html">Título de la noticia</a></h4>
                  </div>
                  <div class="entry-bio">
                    <p>Lorem ipsum dolor sit amet consectetur. Turpis auctor pulvinar fringilla aliquet id. Mi est malesuada eu mattis. Elementum risus convallis pulvinar velit nulla mi rutrum. Id at tristique condimentum viverra vel nec vestibulum.</p>
                  </div>
                  <div class="entry-more"> <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="blog-single-sidebar.html"> 
                      <div class="line"> <span> </span></div><span>Ver Más</span></a></div>
                </div>
              </div>
              <!-- End .Card-->
           
          </div>
          <!-- End .carousel-->
           
        </div>
        <!-- Button -->
         <a class="btn btn--secondary btn-line btn-news" href="/blog.php">Ir a Novedades</a>
      </section>








      <!-- COMENTADO MONETANEAMENTE -->
      <!-- Ultimas novedades  -->
      <!-- <section class="blog blog-grid blog-grid-3" id="blog-2">
        <div class="bg-section"> <img src="assets/images/background/pattern.png" alt="background"/></div>

        <div class="container">
          <div class="row"> 
            <div class="col-12 col-lg-6 offset-lg-3">
              <div class="heading heading-7 text-center">
                <h2 class="heading-title">Artículos y Novedades Recientes sobre Nutrición Animal</h2>
              </div>
            </div>
          </div>
          
        <div class="row g-4 mt-4">
            
          <div class="col-12 col-md-6 col-lg-4">
              <div class="blog-entry">
                <div class="entry-img">
                  <div class="entry-date">
                    <div class="entry-content"><span class="day">20</span><span class="month">ene</span><span class="year">2025</span></div>
                  </div>
                    <a href="blog-single-sidebar.html"><img src="assets/images/blog/grid/1.jpg" alt="Título de noticia"/></a>
                </div>
                <div class="entry-content">
                  <div class="entry-meta">
                    <div class="entry-category"><a href="javascript:void(0)" style="cursor: default;">Nutrición Animal</a>
                    </div>
                    <div class="divider"></div>
                  </div>
                  <div class="entry-title">
                    <h4><a href="blog-single-sidebar.html">Titulo de noticia</a></h4>
                  </div>
                  <div class="entry-bio">
                    <p>Lorem ipsum dolor sit amet consectetur. Turpis auctor pulvinar fringilla aliquet id. Mi est malesuada eu mattis. Elementum risus convallis pulvinar velit nulla mi rutrum. Id at tristique condimentum viverra vel nec vestibulum.</p>
                  </div>
                  <div class="entry-more"> <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="blog-single-sidebar.html"> 
                      <div class="line"> <span> </span></div><span>Ver más</span></a></div>
                </div>
              </div>
          </div>

          <div class="col-12 col-md-6 col-lg-4">
              <div class="blog-entry">
                <div class="entry-img">
                  <div class="entry-date">
                    <div class="entry-content"><span class="day">20</span><span class="month">ene</span><span class="year">2025</span></div>
                  </div>
                    <a href="blog-single-sidebar.html"><img src="assets/images/blog/grid/1.jpg" alt="Título de noticia"/></a>
                </div>
                <div class="entry-content">
                  <div class="entry-meta">
                    <div class="entry-category"><a href="javascript:void(0)" style="cursor: default;">Nutrición Animal</a>
                    </div>
                    <div class="divider"></div>
                  </div>
                  <div class="entry-title">
                    <h4><a href="blog-single-sidebar.html">Titulo de noticia</a></h4>
                  </div>
                  <div class="entry-bio">
                    <p>Lorem ipsum dolor sit amet consectetur. Turpis auctor pulvinar fringilla aliquet id. Mi est malesuada eu mattis. Elementum risus convallis pulvinar velit nulla mi rutrum. Id at tristique condimentum viverra vel nec vestibulum.</p>
                  </div>
                  <div class="entry-more"> <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="blog-single-sidebar.html"> 
                      <div class="line"> <span> </span></div><span>Ver más</span></a></div>
                </div>
              </div>
          </div>

          <div class="col-12 col-md-6 col-lg-4">
              <div class="blog-entry">
                <div class="entry-img">
                  <div class="entry-date">
                    <div class="entry-content"><span class="day">20</span><span class="month">ene</span><span class="year">2025</span></div>
                  </div>
                    <a href="blog-single-sidebar.html"><img src="assets/images/blog/grid/1.jpg" alt="Título de noticia"/></a>
                </div>
                <div class="entry-content">
                  <div class="entry-meta">
                    <div class="entry-category"><a href="javascript:void(0)" style="cursor: default;">Nutrición Animal</a>
                    </div>
                    <div class="divider"></div>
                  </div>
                  <div class="entry-title">
                    <h4><a href="blog-single-sidebar.html">Titulo de noticia</a></h4>
                  </div>
                  <div class="entry-bio">
                    <p>Lorem ipsum dolor sit amet consectetur. Turpis auctor pulvinar fringilla aliquet id. Mi est malesuada eu mattis. Elementum risus convallis pulvinar velit nulla mi rutrum. Id at tristique condimentum viverra vel nec vestibulum.</p>
                  </div>
                  <div class="entry-more"> <a class="btn btn--white btn-line btn-line-before btn-line-inversed" href="blog-single-sidebar.html"> 
                      <div class="line"> <span> </span></div><span>Ver más</span></a></div>
                </div>
              </div>
          </div>


          </div>

        </div>
     
      </section> -->
      
      
      <!--   Team Projects #02 Section - PENDIENTE CAMBIAR LA PALABRA PROJECT (Cambiarla por cual?)  -->
      <section class="team team-projects" id="teamProjects-2">
        <div class="carousel owl-carousel" data-slide="4" data-slide-rs="2" data-autoplay="true" data-nav="false" data-dots="false" data-space="0" data-loop="true" data-speed="800">
          <div class="project" ><a class="img-gallery-item" href="assets/images/team/projects/5.jpg" title="DR.Richard Muldoone" ></a><img src="assets/images/team/projects/5.jpg" alt="DR.Richard Muldoone"/></div>
          <div class="project" ><a class="img-gallery-item" href="assets/images/team/projects/6.jpg" title="DR.Michael Brian" ></a><img src="assets/images/team/projects/6.jpg" alt="DR.Michael Brian"/></div>
          <div class="project" ><a class="img-gallery-item" href="assets/images/team/projects/7.jpg" title="DR.Maria Andaloro" ></a><img src="assets/images/team/projects/7.jpg" alt="DR.Maria Andaloro"/></div>
          <div class="project" ><a class="img-gallery-item" href="assets/images/team/projects/8.jpg" title="DR.Dupree Black" ></a><img src="assets/images/team/projects/8.jpg" alt="DR.Dupree Black"/></div>
        </div>
      </section>

      <!-- CTA Section como en el diseño -->
      <section class="cta cta-5" id="cta-5">
        <div class="bg-section"> <img src="assets/images/background/wavy-pattern.png" alt="background"/></div>
          <div class="container">
          <div class="row align-items-center mb-60">
            <div class="col-12 col-lg-5">
              <div class="heading heading-8 heading-light">
                <h2 class="heading-title">¿Querés conocer más sobre nuestras soluciones?</h2>
                <p class="paragraph">Nuestro equipo técnico y comercial está listo para asesorarte en cada paso.</p>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <div class="video" id="video1">
                <a class="btn btn--white btn-line" href="https://api.whatsapp.com/send/?phone=59895144852&text=Hola%20quisiera%20asesoramiento%20comercial." target="_blank"><i class="fab fa-whatsapp"></i>Contactanos</a>
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
                    <a href="index.html">
                      <img src="assets/images/logo/logo-white.png" alt="Insalcor">
                    </a>
                  </div>

                  <h5 class="footer-title">SECCIONES PRINCIPALES</h5>
                  <ul class="footer-menu">
                    <li><a href="#">Nosotros</a></li>
                    <li><a href="#">Nutrición Animal</a></li>
                    <li><a href="#">Pharma</a></li>
                    <li><a href="#">VetPharma</a></li>
                    <li><a href="#">Novedades</a></li>
                    <li><a href="#">Contacto</a></li>
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
                  <a href="#">Watt</a>
                  <span class="credits-divider">|</span>
                  <a href="#">Strawberry Web Design</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </footer>
            
      <div class="backtop" id="back-to-top" >
        <svg class="bi bi-chevron-up" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708l6-6z"></path>
        </svg>
      </div>
    </div>

    <!-- Modal Producto -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content product-modal">
          <button type="button" class="product-modal-close" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <div class="modal-body p-0">
            <div class="product-modal-inner" id="productModalBody">
              <!-- acá se inyecta #single-product -->
              <div class="text-center p-5">Cargando…</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="assets/js/vendor/jquery-3.6.0.min.js"></script>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/functions.js"></script>
    <script src="assets/js/search.js"></script>
</body>
</html>