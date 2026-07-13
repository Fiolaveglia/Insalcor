<?php
require __DIR__ . '/inc/public.php';
i18n_begin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = $id > 0 ? pub_noticia($id) : null;
if ($item) {
    // Count a view (public read of a published article).
    db()->prepare('UPDATE noticias SET vistas = vistas + 1 WHERE id = ?')->execute([$item['id']]);
    $d = date_parts($item['published_at'] ?: $item['created_at']);
}
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Insalcor – <?= e($item['titulo'] ?? t('common.article_not_found')) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link href="assets/images/favicon/favicon.ico" rel="icon"/>
  <link href="assets/css/vendor.min.css" rel="stylesheet"/>
  <link href="assets/css/style.css" rel="stylesheet"/>
</head>
<body data-i18n-base="assets/i18n" data-lang="es" data-api-root="." data-asset-prefix="">
  <div class="wrapper clearfix">
    <header class="header header-light header-topbar" id="navbar-spy">
      <nav class="navbar navbar-expand-xl navbar-sticky" id="primary-menu">
        <a class="navbar-brand" href="index.html"><img class="logo logo-dark" src="assets/images/logo/logo-dark.png" alt="Insalcor"/></a>
        <div class="collapse navbar-collapse" id="navbarContent">
          <ul class="navbar-nav">
            <li class="nav-item"><a href="index.html"><span data-i18n="nav.home">INICIO</span></a></li>
            <li class="nav-item"><a href="nosotros.html"><span data-i18n="nav.about">NOSOTROS</span></a></li>
            <li class="nav-item"><a href="nutricion-animal.php"><span data-i18n="nav.business">ÁREAS DE NEGOCIO</span></a></li>
            <li class="nav-item active"><a href="blog.php"><span data-i18n="nav.news">NOVEDADES</span></a></li>
            <li class="nav-item"><a href="contact.html"><span data-i18n="nav.contact">CONTACTO</span></a></li>
          </ul>
        </div>
      </nav>
    </header>
    <section class="blog" style="padding:80px 0">
      <div class="container">
        <?php if ($item):
            $img = asset($item['imagen']);
        ?>
        <div class="blog-entry">
          <?php if ($img): ?><div class="entry-img mb-4"><img src="<?= e($img) ?>" alt="<?= e($item['titulo']) ?>" class="img-fluid"/></div><?php endif; ?>
          <div class="entry-meta mb-2">
            <span class="entry-category"><?= e($item['categoria']) ?></span>
            <span class="ms-2 text-muted"><?= e($d['day']) ?> <?= e($d['month']) ?> <?= e($d['year']) ?></span>
          </div>
          <h1 class="entry-title"><?= e($item['titulo']) ?></h1>
          <div class="entry-bio lead"><?= e($item['extracto']) ?></div>
          <div class="entry-content mt-4"><?= $item['contenido'] ?></div>
        </div>
        <?php else: ?>
        <p><?= e(t('common.article_not_found')) ?></p>
        <?php endif; ?>
        <p class="mt-4"><a href="blog.php">&larr; <span data-i18n="common.back_news">Volver a Novedades</span></a></p>
      </div>
    </section>
  </div>
  <script src="assets/js/vendor/jquery-3.6.0.min.js"></script>
  <script src="assets/js/vendor.min.js"></script>
  <script src="assets/js/functions.js"></script>
</body>
</html>
