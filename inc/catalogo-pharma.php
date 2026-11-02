<?php
/**
 * Bloque de productos de un área del catálogo Pharma (Pharma / VetPharma /
 * Suplementos). Lo incluye render_catalogo_pharma() en inc/public.php, que
 * define: $area, $f (filtros del área), $items, $conteos, $grupos, $slug.
 *
 * Orden según el documento "Filtros Pharma": área (las tarjetas, arriba de
 * todo en la página) → país (banderas) → título → categoría (sidebar).
 */

// Pedido directo por URL (no incluido desde render_catalogo_pharma()).
if (!isset($area, $f, $items, $grupos, $slug)) {
    http_response_code(404);
    exit;
}

/** Lista de desplegables de un grupo, marcando los compuestos repetidos. */
$renderLista = static function (array $lista): string {
    $repetidos = array_filter(array_count_values(array_map(
        static fn ($it) => normalizar_texto(campo_i18n($it, 'nombre')),
        $lista
    )), static fn ($n) => $n > 1);
    $html = '';
    foreach ($lista as $it) {
        $html .= render_pharma_item($it, isset($repetidos[normalizar_texto(campo_i18n($it, 'nombre'))]));
    }
    return $html;
};

/** Grupos de una categoría: con encabezado (marca / subgrupo) o sin agrupar. */
$renderGrupos = static function (array $gruposCat, string $nivel) use ($renderLista): string {
    $html = '';
    foreach ($gruposCat as $grupo => $lista) {
        if ($grupo === '') {
            $html .= '<div class="pharma-grupo">' . $renderLista($lista) . '</div>';
            continue;
        }
        $html .= '<div class="pharma-grupo"><' . $nivel . ' class="pharma-grupo__titulo">'
            . e(grupo_pharma_label((string) $grupo)) . ' <span>(' . count($lista) . ')</span></' . $nivel . '>'
            . $renderLista($lista) . '</div>';
    }
    return $html;
};

$hayFiltros = $f['pais'] !== '' || $f['categoria'] !== '' || $f['q'] !== '';
?>
      <section class="shop pharma-catalogo" id="shop-<?= e($slug) ?>">
        <div class="container">

          <!-- Filtro 2: país -->
          <div class="row">
            <div class="col-12">
              <div class="pharma-paises-wrap">
                <p class="pharma-paises__titulo"><?= e(t('pharma.country_title')) ?></p>
                <?= render_pais_filter($area, $f) ?>
                <p class="pharma-paises__estado">
                  <?= e($f['pais'] !== '' ? t('pharma.country_selected', ['pais' => $f['pais']]) : t('pharma.country_all')) ?>
                </p>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-6 offset-lg-3">
              <div class="heading heading-7 text--center">
                <h2 class="heading-title"><?= e(t('shop.products_of')) ?> <span class="color-segment"><?= e(area_label($area)) ?></span></h2>
              </div>
            </div>
          </div>

          <!-- Contacto: visible mientras se recorren los productos -->
          <!-- <div class="row">
            <div class="col-12">
              <div class="pharma-contacto">
                <i class="fas fa-envelope"></i>
                <span><?= e(t('pharma.contact_text')) ?></span>
                <a class="js-consultar" href="contact.html" data-area="<?= e($area) ?>"><?= e(t('pharma.contact_cta')) ?></a>
              </div>
            </div>
          </div> -->

          <div class="row">
            <!-- Sidebar: búsqueda + Filtro 3 (categoría) -->
            <div class="col-12 col-lg-3">
              <div class="sidebar sidebar-shop">
                <div class="widget widget-search">
                  <div class="widget-title">
                    <h5><?= e(t('shop.search')) ?></h5>
                  </div>
                  <div class="widget-content">
                    <form class="form-search" method="get" action="#segment-<?= e($slug) ?>">
                      <?php if (current_lang() !== 'es'): ?><input type="hidden" name="lang" value="<?= e(current_lang()) ?>"/><?php endif; ?>
                      <input type="hidden" name="seg" value="<?= e($slug) ?>"/>
                      <?php if ($f['pais'] !== ''): ?><input type="hidden" name="pais" value="<?= e($f['pais']) ?>"/><?php endif; ?>
                      <?php if ($f['categoria'] !== ''): ?><input type="hidden" name="categoria" value="<?= e($f['categoria']) ?>"/><?php endif; ?>
                      <div class="input-group">
                        <input class="form-control" type="text" name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('common.search_placeholder')) ?>"/><span class="input-group-btn">
                          <button class="btn" type="submit" aria-label="<?= e(t('shop.search')) ?>"><i class="icon-search"></i></button></span>
                      </div>
                    </form>
                  </div>
                </div>

                <div class="widget especie pharma-categorias">
                  <div class="widget-title">
                    <h5><?= e(t('shop.categories')) ?></h5>
                  </div>
                  <div class="widget-content">
                    <?= render_categoria_filter($area, $f, $conteos) ?>
                  </div>
                </div>
              </div>
            </div>

            <!-- Listado -->
            <div class="col-12 col-lg-9">
              <div class="shop-options">
                <div class="products-show">
                  <p>
                    <?= e(t('common.showing_products', ['n' => count($items)])) ?>
                    <?php if ($hayFiltros): ?>
                      · <a class="pharma-limpiar" href="<?= e(pharma_url($area, $f, ['pais' => '', 'categoria' => '', 'q' => ''])) ?>"><?= e(t('pharma.clear_filters')) ?></a>
                    <?php endif; ?>
                  </p>
                </div>
              </div>

              <?php if (!$items): ?>
                <p class="pharma-vacio"><?= e(t('pharma.no_results')) ?></p>
              <?php elseif ($f['categoria'] !== ''): ?>
                <?= $renderGrupos($grupos[$f['categoria']] ?? [], 'h3') ?>
              <?php else: ?>
                <?php foreach ($grupos as $categoria => $gruposCat): ?>
                  <div class="pharma-categoria">
                    <h3 class="pharma-categoria__titulo">
                      <a href="<?= e(pharma_url($area, $f, ['categoria' => $categoria])) ?>"><?= e(categoria_label((string) $categoria)) ?></a>
                      <span>(<?= (int) array_sum(array_map('count', $gruposCat)) ?>)</span>
                    </h3>
                    <?= $renderGrupos($gruposCat, 'h4') ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>
