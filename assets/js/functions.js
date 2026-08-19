/*global jQuery */
/* Contents
// ------------------------------------------------>
	1.  LOADING SCREEN
	2.  BACKGROUND INSERT
	3.	NAV MODULE
	4.  MOBILE MENU
	5.  NAVBAR STICKY
	6.  AJAX MAILCHIMP
	7.  AJAX CAMPAIGN MONITOR          
	8.  OWL CAROUSEL
	9.  MAGNIFIC POPUP       
	10. MAGNIFIC POPUP VIDEO
	11. BACK TO TOP
	12. GALLERY FLITER
	13. SCROLL TO
	14. PROGRESS BAR
	15. SLIDER RANGE
	16. AJAX CONTACT FORM
	17. PARALLAX FOOTER
	18. TWITTER FEED
	19. PROGRESS BAR
	20. WOW ANIMATED
	21. MAP POINTERS
	22. MOUSE CURSOR CUSTOMIZATION
	23. NICE SELECT INPUT
    24. TIMETABLE FILTERIIG 
    25. ACCORDION ACTIVATION
    26. LOAD MORE BUTTON
    27. SHOP PRODUCT QUANTITY
*/
(function($) {
    "use strict";
    /* ------------------  LOADING SCREEN ------------------ */

    $(window).on("load", function() {
        $(".preloader").fadeOut(5000);
        $(".preloader").remove();
    });

    /* ------------------  Background INSERT ------------------ */

    var $bgSection = $(".bg-section");
    var $bgPattern = $(".bg-pattern");
    var $colBg = $(".col-bg");

    $bgSection.each(function() {
        var bgSrc = $(this).children("img").attr("src");
        var bgUrl = 'url(' + bgSrc + ')';
        $(this).parent().css("backgroundImage", bgUrl);
        $(this).parent().addClass("bg-section");
        $(this).remove();
    });

    $bgPattern.each(function() {
        var bgSrc = $(this).children("img").attr("src");
        var bgUrl = 'url(' + bgSrc + ')';
        $(this).parent().css("backgroundImage", bgUrl);
        $(this).parent().addClass("bg-pattern");
        $(this).remove();
    });

    $colBg.each(function() {
        var bgSrc = $(this).children("img").attr("src");
        var bgUrl = 'url(' + bgSrc + ')';
        $(this).parent().css("backgroundImage", bgUrl);
        $(this).parent().addClass("col-bg");
        $(this).remove();
    });

    /* ------------------  NAV MODULE  ------------------ */

    var $moduleIcon = $(".module-icon"),
        $moduleCancel = $(".module-cancel");
    $moduleIcon.on("click", function(e) {
        $(this).parent().siblings().removeClass('module-active'); // Remove the class .active form any sibiling.
        $(this).parent(".module").toggleClass("module-active"); //Add the class .active to parent .module for this element.
        e.stopPropagation();
    });
    $('.module-search .module-icon').click(function() {
        $('.module-fullscreen').toggleClass('module-active');
    });
    // If Click on [ Search-cancel ] Link
    $('.module-fullscreen .module-cancel').on("click", function(e) {
        e.stopPropagation();
        e.preventDefault();
        $(".module-fullscreen").removeClass("module-active");
    });

    $('.module-cart .module-icon').click(function() {
        $(this).siblings('.cart-box').toggleClass('active');
    });

    //Close Modules On Clicking OutSide
    $(document).click(function() {
        if ($('.cart-box').hasClass('active')) {
            $('.module-cart .module-icon').click();
        }
    });

    //Close Modules On Pressing Esc
    $(document).keyup(function(e) {
        // ESCAPE key pressed
        if (e.keyCode == 27) {
            if ($('.cart-box').hasClass('active')) {
                $('.module-cart .module-icon').click();
            }
            if ($('.module-search').hasClass('module-active')) {
                $('.module-search .module-cancel').click();
            }
        }
    });

    $('.cart-box , .module-cart .module-icon , .module-search .form-search , .module-language').click(function(e) {
        e.stopPropagation();
    });

    /* ------------------  MOBILE MENU ------------------ */
    var $w = $(window);
    var $wWidth = $w.width();
    var mobile_resolution_size = "1200";
    var $dropToggle = $("[data-toggle='dropdown']");
    $dropToggle.on("click", function(event) {
        $(this).each(() => {
            if ($wWidth <= mobile_resolution_size && $(this).attr('href') === '#') {
                event.preventDefault();
                event.stopPropagation();
                $(this).parent().siblings().removeClass("show");
                $(this).parent().toggleClass("show");
            } else if ($wWidth <= mobile_resolution_size && !$(this).attr('href') !== '#') {
                event.preventDefault();
                event.stopPropagation();
                $(this).parent().siblings().removeClass("show");
                $(this).parent().toggleClass("show");
                $(this).children('span').on('click', () => {
                    window.location.href = $(this).attr('href');
                })
            }
        })

    });

    /* ------------------ NAVBAR STICKY ------------------ */

    $(window).scroll(function() {
        if ($(document).scrollTop() > 100) {
            $('.navbar-sticky').addClass('navbar-fixed');
        } else {
            $('.navbar-sticky').removeClass('navbar-fixed');
        }
    });

    /* ------------------  AJAX MAILCHIMP ------------------ */

    $('.mailchimp').ajaxChimp({
        url: "http://wplly.us5.list-manage.com/subscribe/post?u=91b69df995c1c90e1de2f6497&id=aa0f2ab5fa", //Replace with your own mailchimp Campaigns URL.
        callback: chimpCallback

    });

    function chimpCallback(resp) {
        if (resp.result === 'success') {
            $('.subscribe-alert').html('<div class="alert alert-success">' + resp.msg + '</div>').fadeIn(1000);
            //$('.subscribe-alert').delay(6000).fadeOut();

        } else if (resp.result === 'error') {
            $('.subscribe-alert').html('<div class="alert alert-danger">' + resp.msg + '</div>').fadeIn(1000);
        }
    }

    $('.subscribe-alert').on('click', function() {
        $(this).fadeOut();
    });

    /* ------------------  AJAX CAMPAIGN MONITOR  ------------------ */

    $('#campaignmonitor').submit(function(e) {
        e.preventDefault();
        $.getJSON(
            this.action + "?callback=?",
            $(this).serialize(),
            function(data) {
                if (data.Status === 400) {
                    alert("Error: " + data.Message);
                } else { // 200
                    alert("Success: " + data.Message);
                }
            });
    });

/* ------------------ OWL CAROUSEL ------------------ */

var $carouselDirection = $("html").attr("dir");
var $carouselrtl = $carouselDirection == "rtl";

/* 1) Carruseles normales (todos menos el continuo) */
$(".carousel").not(".carousel-continuo").each(function() {
    var $Carousel = $(this);

    $Carousel.owlCarousel({
        loop: $Carousel.data('loop'),
        autoplay: $Carousel.data("autoplay"),
        margin: $Carousel.data('space') || 0,
        nav: $Carousel.data('nav'),
        dots: $Carousel.data('dots'),
        center: $Carousel.data('center'),
        rtl: $carouselrtl,
        dotsSpeed: $Carousel.data('speed') || 600,
        smartSpeed: $Carousel.data('speed') || 600,
        responsive: {
            0:    { items: 1 },
            768:  { items: $Carousel.data('slide-rs') },
            1000: { items: $Carousel.data('slide') }
        }
    });
});

/* 2) Carrusel continuo de alianzas */
var $continuous = $(".carousel-continuo");

if ($continuous.length) {
    $continuous.owlCarousel({
        loop: true,
        autoplay: true,
        autoplayHoverPause: false,
        margin: 30,
        nav: false,
        dots: false,
        rtl: $carouselrtl,

        
        autoplayTimeout: 2500,   // cada cuánto avanza
        autoplaySpeed:  2400,    // duración de la animación
        smartSpeed:     2400,
        slideTransition: 'linear',

        responsive: {
            0:    { items: 2 },
            768:  { items: 4 },
            1000: { items: 6 }
        }
    });
}


$(".custom-carousel").each(function() {
    var $Carousel = $(this);
    $Carousel.owlCarousel({
        loop: $Carousel.data('loop'),
        autoplay: $Carousel.data("autoplay"),
        margin: $Carousel.data('space'),
        nav: $Carousel.data('nav'),
        dots: $Carousel.data('dots'),
        center: $Carousel.data('center'),
        dotsSpeed: $Carousel.data('speed'),
        dotsContainer: '#carousel-custom-dots',
        responsive: {
            0:    { items: 1 },
            768:  { items: $Carousel.data('slide-rs') },
            1000: { items: $Carousel.data('slide') }
        }
    });
});

$('.custom-carousel').owlCarousel({
    thumbs: true,
    thumbsPrerendered: true
});

$(".slider-carousel").each(function() {
    var $Carousel = $(this);
    $Carousel.owlCarousel({
        loop: $Carousel.data('loop'),
        autoplay: $Carousel.data("autoplay"),
        margin: $Carousel.data('space'),
        nav: $Carousel.data('nav'),
        dots: $Carousel.data('dots'),
        center: $Carousel.data('center'),
        dotsSpeed: $Carousel.data('speed'),
        responsive: {
            0:    { items: 1 },
            768:  { items: $Carousel.data('slide-rs') },
            1000: { items: $Carousel.data('slide') }
        }
    });
});


    // Clicking On Thumbs
    $('.testimonial-thumbs .testimonial-thumb').click(function() {
        $(this).siblings(".testimonial-thumb").removeClass('active');
        $(this).addClass('active');
        $(".custom-carousel").trigger('to.owl.carousel', [$(this).index(), 300]);
    });

    // Draging The Carousel And The Thumbs Still has Active Class 
    $(".custom-carousel").on('changed.owl.carousel', function(event) {
        var items = event.item.count; // Number of items
        var item = event.item.index; // Position of the current item
        var owlDots = document.querySelectorAll('.testimonial-thumbs .testimonial-thumb');
        if (owlDots.length > 0) {
            owlDots[item].click()
        }
    })

    // Clicking The Custom Nav
    $('.testimonials-holder .custom-navs .next').click(function() {
        $(".custom-carousel").trigger('next.owl.carousel');
    });

    $('.testimonials-holder .custom-navs .prev').click(function() {
        $(".custom-carousel").trigger('prev.owl.carousel');
    });

    /* ------------------ MAGNIFIC POPUP ------------------ */

    var $imgPopup = $(".img-popup");
    $imgPopup.magnificPopup({
        type: "image"
    });
    $('.img-gallery-item').magnificPopup({
        type: 'image',
        gallery: {
            enabled: true
        }
    });

    /* ------------------  MAGNIFIC POPUP VIDEO ------------------ */

    $('.popup-video,.popup-gmaps').magnificPopup({
        disableOn: 700,
        mainClass: 'mfp-fade',
        removalDelay: 0,
        preloader: false,
        fixedContentPos: false,
        type: 'iframe',
        iframe: {
            markup: '<div class="mfp-iframe-scaler">' +
                '<div class="mfp-close"></div>' +
                '<iframe class="mfp-iframe" frameborder="0" allowfullscreen></iframe>' +
                '</div>',
            patterns: {
                youtube: {
                    index: 'youtube.com/',
                    id: 'v=',
                    src: '//www.youtube.com/embed/%id%?autoplay=1'
                }
            },
            srcAction: 'iframe_src',
        }
    });

    /* ------------------  BACK TO TOP ------------------ */

    var backTop = $('#back-to-top');

    if (backTop.length) {
        var scrollTrigger = 200, // px
            backToTop = function() {
                var scrollTop = $(window).scrollTop();
                if (scrollTop > scrollTrigger) {
                    backTop.addClass('show');
                } else {
                    backTop.removeClass('show');
                }
            };
        backToTop();
        $(window).on('scroll', function() {
            backToTop();
        });
        backTop.on('click', function(e) {
            e.preventDefault();
            $('html,body').animate({
                scrollTop: 0
            }, 700);
        });
    }

    /* ------------------ GALLERY FLITER ------------------ */

    var $galleryFilter = $(".gallery-filter"),
        galleryLength = $galleryFilter.length,
        protfolioFinder = $galleryFilter.find("a"),
        $galleryAll = $("#gallery-all");

    // init Isotope For gallery
    protfolioFinder.on("click", function(e) {
        e.preventDefault();
        $galleryFilter.find("a.active-filter").removeClass("active-filter");
        $(this).addClass("active-filter");
    });
    if (galleryLength > 0) {
        $galleryAll.imagesLoaded().progress(function() {
            $galleryAll.isotope({
                filter: "*",
                animationOptions: {
                    duration: 750,
                    itemSelector: ".gallery-item",
                    easing: "linear",
                    queue: false,
                }
            });
        });
    }

    protfolioFinder.on("click", function(e) {
        e.preventDefault();
        var $selector = $(this).attr("data-filter");
        $galleryAll.imagesLoaded().progress(function() {
            $galleryAll.isotope({
                filter: $selector,
                animationOptions: {
                    duration: 750,
                    itemSelector: ".gallery-item",
                    easing: "linear",
                    queue: false,
                }
            });
            return false;
        });
    });

    /* ------------------  SCROLL TO ------------------ */

    var aScroll = $('a[data-scroll="scrollTo"]');
    aScroll.on('click', function(event) {
        var target = $($(this).attr('href'));
        if (target.length) {
            event.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top
            }, 1000);
            if ($(this).hasClass("menu-item")) {
                $(this).parent().addClass("active");
                $(this).parent().siblings().removeClass("active");
            }
        }
    });

    /* ------------------ PROGRESS BAR ------------------ */

    $(".progressbar").each(function() {
        $(this).waypoint(function() {
            var progressBar = $(".progress-bar"),
                progressBarTitle = $(".progress-title .value");
            progressBar.each(function() {
                $(this).css("width", $(this).attr("aria-valuenow") + "%");
            });
            progressBarTitle.each(function() {
                $(this).css('opacity', 1);
            });
        }, {
            triggerOnce: true,
            offset: 'bottom-in-view'
        });
    });

    /* ------------------ SLIDER RANGE ------------------ */

    var $sliderRange = $("#slider-range"),
        $sliderAmount = $("#amount");
    $sliderRange.slider({
        range: true,
        min: 0,
        max: 500,
        values: [50, 300],
        slide: function(event, ui) {
            $sliderAmount.val("$" + ui.values[0] + " - $" + ui.values[1]);
        }
    });
    $sliderAmount.val("$" + $sliderRange.slider("values", 0) + " - $" + $sliderRange.slider("values", 1));

    /* ------------------  AJAX CONTACT FORM  ------------------ */

    var contactForm = $(".contactForm"),
        contactResult = $('.contact-result');
    contactForm.validate({
        debug: false,
        submitHandler: function(contactForm) {
            $(contactResult, contactForm).html('Por favor espere...');
            $.ajax({
                type: "POST",
                url: "assets/php/contact.php",
                data: $(contactForm).serialize(),
                timeout: 20000,
                success: function(msg) {
                    $(contactResult, contactForm).html('<div class="alert alert-success" role="alert"><strong>Gracias. Te responderemos a la brevedad.</strong></div>').delay(3000).fadeOut(2000);
                },
                error: $('.thanks').show()
            });
            return false;
        }
    });


    /* ------------------  PARALLAX FOOTER ------------------ */

    siteFooter();
    $(window).resize(function() {
        siteFooter();
    });

    function siteFooter() {
        var siteContent = $('#wrapperParallax');

        var siteFooter = $('#footerParallax');
        var siteFooterHeight = siteFooter.height();

        siteContent.css({
            "margin-bottom": siteFooterHeight
        });
    };

    /* ------------------  WOW Animated ------------------ */

    var wow = new WOW({
        boxClass: 'wow', // animated element css class (default is wow)
        animateClass: 'animated', // animation css class (default is animated)
        offset: 50, // distance to the element when triggering the animation (default is 0)
        mobile: false, // trigger animations on mobile devices (default is true)
        live: true // act on asynchronously loaded content (default is true)

    });
    wow.init();

    /* ------------------  MAP POINTERS ------------------ */

    var imagePointer = $('.img-hotspot .img-hotspot-pointer');
    var pointerInfo = $('.img-hotspot .img-hotspot-pointer .info');
    imagePointer.each(function(index) {
        $(this).css('top', $(this).data('spot-y'));
        $(this).css('left', $(this).data('spot-x'));
    })
    pointerInfo.each(function(index) {
        $(this).css('top', $(this).data('info-y'));
        $(this).css('left', $(this).data('info-x'));
    })


    /* ------------------  NICE SELECT INPUT  ------------------ */

    $('select').niceSelect();

    /* ------------------  TIMETABLE FILTERIIG  ------------------ */

    $('.table-sort .nice-select .list li').each(function() {
        $(this).on('click', () => {
            console.log($(this).data('value'));
            $('.time-table').removeClass('active');
            if ($(this).data('value') === '*') {
                $('.time-table').addClass('active');
            } else {
                console.log($("[data-target='" + $(this).data('value') + "']"))
                $('.table-column').find("[data-target='" + $(this).data('value') + "']").addClass('active')
            }
        })
    })

    /* ------------------  ACCORDION ACTIVATION  ------------------ */

    $('.collapse').on('shown.bs.collapse', function() {
        $(this).parent('.card').addClass('active-acc');
    });
    $('.collapse').on('hidden.bs.collapse', function() {
        $(this).parent('.card').removeClass('active-acc');
    });

    /* ------------------  LOAD MORE BUTTON  ------------------ */

    $("#loadMore").on("click", function(e) {
        e.preventDefault();
        $(".content.d-none").slice(0, 3).removeClass('d-none');
        if ($(".content.d-none").length == 0) {
            $("#loadMore").addClass("d-none");
        }
    })

    /* ------------------  SHOP PRODUCT QUANTITY  ------------------ */

    $('.product-quantity span ').on('click', 'a.plus, a.minus', function() {
        // Get current quantity values
        var qty = $(this).parents('.product-quantity').find('.pro-qunt');
        var val = parseFloat(qty.val());
        var max = parseFloat(qty.data('max'));
        var min = parseFloat(qty.data('min'));
        var step = parseFloat(qty.data('step'));

        // Check If Quantity value is undefined or non numeric 
        if (isNaN(val)) {
            var val = 0;
        }

        // Change the value if plus or minus
        if ($(this).is('.plus')) {
            if (max && (max <= val)) {
                qty.val(max);
            } else {
                qty.val(val + step);
            }
        } else {
            if (min && (min >= val)) {
                qty.val(min);
            } else if (val > 1) {
                qty.val(val - step);
            }
        }
    });

    // Cerrar modal de producto (registrado una sola vez)
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".product-modal-close");
        if (!btn) return;

        const modalEl = document.getElementById("productModal");

        if (window.bootstrap && bootstrap.Modal) {
            const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            inst.hide();
            return;
        }

        if (window.jQuery && jQuery.fn.modal) {
            jQuery(modalEl).modal("hide");
            return;
        }

        modalEl.classList.remove("show");
        modalEl.style.display = "none";
        document.body.classList.remove("modal-open");
    });

    // Scipt para el modal 
    document.addEventListener("click", async function (e) {
        const link = e.target.closest(".js-open-product");
        if (!link) return;

        e.preventDefault();

        const url = link.getAttribute("href") || "product-single.html";

        const modalEl = document.getElementById("productModal");
        const bodyEl = document.getElementById("productModalBody");

        bodyEl.innerHTML = '<div class="text-center p-5">Cargando…</div>';

        // Abrir modal
        const modal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
        modal.show();

        try {
            const res = await fetch(url, { cache: "no-store" });
            const html = await res.text();

            const doc = new DOMParser().parseFromString(html, "text/html");

            const singleProduct = doc.querySelector("#single-product");
            if (!singleProduct) throw new Error("No se encontró #single-product en " + url);

            bodyEl.innerHTML = "";
            bodyEl.appendChild(singleProduct);

        } catch (err) {
            bodyEl.innerHTML = '<div class="p-5 text-center">No se pudo cargar el detalle.</div>';
            console.error(err);
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        const modalEl = document.getElementById("productModal");
        if (modalEl && modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
    });

    // Filtro Pharma / VetPharma

        (function(){
            var buttons = document.querySelectorAll('.segment-card');
            var segments = document.querySelectorAll('.segment-content');

            function showSegment(targetSelector){
            segments.forEach(function(s){
                s.classList.remove('is-visible');
            });
            var target = document.querySelector(targetSelector);
            if(target) target.classList.add('is-visible');

            buttons.forEach(function(b){
                var active = (b.getAttribute('data-target') === targetSelector);
                b.classList.toggle('is-active', active);
                b.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            // opcional: bajar al contenido
            // document.querySelector(targetSelector).scrollIntoView({behavior:'smooth', block:'start'});
            }

            buttons.forEach(function(btn){
            btn.addEventListener('click', function(){
                showSegment(btn.getAttribute('data-target'));
            });
            });

            // Honor a #segment-… hash (e.g. after a VetPharma filter reload),
            // otherwise default to Pharma.
            var initial = '#segment-pharma';
            if (location.hash && document.querySelector(location.hash + '.segment-content')) {
                initial = location.hash;
            }
            showSegment(initial);
        })();




    /* ---------------------------------------------------------
            Current Year Copyright area
        --------------------------------------------------------- */
        $(".current-year").text((new Date).getFullYear());


        /* ========================================
   LIMPIEZA Y ENVÍO AJAX DEL FORMULARIO
   ======================================== */
(function($) {
    'use strict';
    
    // Esperar a que el DOM esté listo
    $(document).ready(function() {
        
        var $form = $('.contactForm');
        
        if ($form.length === 0) {
            console.log('Formulario no encontrado');
            return;
        }
        
        console.log('Formulario de contacto detectado');
        
        $form.on('submit', function(e) {
            e.preventDefault(); // Prevenir envío tradicional
            
            console.log('Enviando formulario...');
            
            var $submitButton = $form.find('button[type="submit"]');
            var $resultDiv = $form.find('.contact-result');
            var originalButtonText = $submitButton.text();
            
            // Deshabilitar botón
            $submitButton.prop('disabled', true).text('Enviando...');
            
            // Enviar formulario con AJAX
            $.ajax({
                type: 'POST',
                url: $form.attr('action'),
                data: $form.serialize(),
                success: function(response) {
                    console.log('Respuesta recibida');
                    
                    // Mostrar respuesta
                    $resultDiv.html(response);
                    
                    // Si es exitoso, limpiar formulario
                    if (response.indexOf('alert-success') !== -1 || response.indexOf('Gracias por contactarnos') !== -1) {
                        console.log('Éxito - limpiando formulario');
                        
                        // Limpiar formulario
                        $form[0].reset();
                        
                        // Scroll al mensaje de éxito
                        $('html, body').animate({
                            scrollTop: $resultDiv.offset().top - 100
                        }, 500);
                        
                        // Opcional: Ocultar mensaje después de 5 segundos
                        setTimeout(function() {
                            $resultDiv.fadeOut(function() {
                                $(this).html('').show();
                            });
                        }, 5000);
                    }
                },
                error: function() {
                    console.log('Error al enviar');
                    $resultDiv.html('<div class="alert alert-danger" role="alert">Error al enviar el mensaje. Por favor intenta nuevamente.</div>');
                },
                complete: function() {
                    // Rehabilitar botón
                    $submitButton.prop('disabled', false).text(originalButtonText);
                }
            });
        });
    });
    
})(jQuery);    



}(jQuery));