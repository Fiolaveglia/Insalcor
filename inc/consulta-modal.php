<?php
/**
 * Modal "Consultar por este producto" de pharma-vetpharma.php.
 *
 * Es el mismo formulario de contact.html (clase .contactForm: lo envía el
 * handler AJAX de functions.js a assets/php/contact.php), con dos campos
 * ocultos que completa el JS al abrirlo: el producto y el área. Con eso
 * contact.php manda el email a la casilla de Pharma (ver inc/consulta.php).
 */
if (!function_exists('t')) {
    http_response_code(404);
    exit;
}
?>
    <div class="modal fade" id="consultaModal" tabindex="-1" aria-labelledby="consultaModalTitulo" aria-hidden="true"
         data-plantilla="<?= e(t('pharma.inquiry_prefill')) ?>">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content consulta-modal">
          <div class="modal-header">
            <h5 class="modal-title" id="consultaModalTitulo"><?= e(t('pharma.inquiry_title')) ?></h5>
            <!-- El ícono de .btn-close de Bootstrap no se ve con el tema: se usa el
                 mismo de Font Awesome que cierra el buscador. -->
            <button type="button" class="consulta-modal__cerrar" data-bs-dismiss="modal" aria-label="<?= e(t('pharma.inquiry_close')) ?>"><i class="fas fa-times"></i></button>
          </div>
          <div class="modal-body">
            <p class="consulta-modal__producto d-none">
              <span><?= e(t('pharma.inquiry_product')) ?>:</span> <strong class="consulta-producto"></strong>
            </p>
            <form class="contactForm" method="post" action="assets/php/contact.php">
              <input type="hidden" name="contact-producto" value=""/>
              <input type="hidden" name="contact-area" value=""/>
              <div class="row">
                <div class="col-12 col-md-6">
                  <input class="form-control" type="text" name="contact-name" placeholder="Nombre" data-i18n-placeholder="contact.name" required=""/>
                </div>
                <div class="col-12 col-md-6">
                  <input class="form-control" type="text" name="contact-empresa" placeholder="Empresa" data-i18n-placeholder="contact.company" required=""/>
                </div>
                <div class="col-12 col-md-6">
                  <input class="form-control" type="email" name="contact-email" placeholder="Email*" data-i18n-placeholder="contact.email" required="" style="text-transform: lowercase;"/>
                </div>
                <div class="col-12 col-md-6">
                  <input class="form-control" type="tel" name="contact-phone" placeholder="Teléfono" data-i18n-placeholder="contact.phone" pattern="[0-9]{9,}" title="Debe contener al menos 9 dígitos" minlength="9"/>
                </div>
                <div class="col-12">
                  <textarea class="form-control" name="contact-message" rows="4" placeholder="Mensaje" data-i18n-placeholder="contact.message" required=""></textarea>
                </div>
                <!-- Honeypot anti-spam: oculto para personas, los bots lo completan -->
                <div style="position:absolute;left:-9999px" aria-hidden="true">
                  <input type="text" name="contact-website" tabindex="-1" autocomplete="off"/>
                </div>
                <div class="col-12">
                  <button class="btn btn--secondary btn--block" data-i18n="contact.send">Enviar</button>
                </div>
                <div class="col-12">
                  <div class="contact-result"></div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
