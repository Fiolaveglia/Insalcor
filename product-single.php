<?php
require __DIR__ . '/inc/public.php';
i18n_begin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = $id > 0 ? pub_producto($id) : null;
$contactHref = 'contact.html';
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?= e(current_lang()) ?>">
  <head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="author" content="Insalcor"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
    <meta name="description" content="Insalcor - <?= e($item['nombre'] ?? 'Producto') ?>"/>
    <title>Insalcor – <?= e($item['nombre'] ?? t('common.product_not_found')) ?></title>
    <link href="assets/images/favicon/favicon.png" rel="icon"/>
    <link rel="preconnect" href="https://fonts.gstatic.com"/>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&amp;family=Roboto:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&amp;family=Rubik:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&amp;display=swap" rel="stylesheet"/>
    <link href="assets/css/vendor.min.css" rel="stylesheet"/>
    <link href="assets/css/style.css" rel="stylesheet"/>
    <link href="assets/css/search.css" rel="stylesheet"/>
  </head>
  <body dir="ltr" data-asset-prefix="">
      <section class="single-product" id="single-product">
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
                <div class="product-desc"><?= $item['descripcion'] ?></div>
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
          <p><?= e(t('common.product_not_found')) ?></p>
          <?php endif; ?>
        </div>
      </section>

    <script src="assets/js/vendor/jquery-3.6.0.min.js"></script>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/functions.js"></script>
    <script src="assets/js/search.js"></script>
</body>
</html>
