/**
 * Sistema de búsqueda - Insalcor
 * Agrega funcionalidad al buscador existente
 * VERSIÓN PARA search.php en carpeta /php/
 */

(function($) {
    'use strict';

    // Configuración
    const searchConfig = {
        minChars: 3,  // Mínimo de caracteres para buscar
        delay: 500,   // Delay antes de buscar (milisegundos)
        maxResults: 5 // Máximo de resultados en sugerencias
    };

    let searchTimeout;

    /**
     * Inicializa el sistema de búsqueda
     */
    function initSearch() {
        const $searchForm = $('.module-fullscreen .form-search');
        const $searchInput = $searchForm.find('input[type="text"]');
        
        if ($searchForm.length === 0 || $searchInput.length === 0) {
            console.log('⚠️ Formulario de búsqueda no encontrado');
            return;
        }

        console.log('🔍 Sistema de búsqueda inicializado');

        // Prevenir submit del formulario por defecto
        $searchForm.on('submit', function(e) {
            e.preventDefault();
            performSearch();
        });

        // Búsqueda en tiempo real (opcional)
        $searchInput.on('keyup', function(e) {
            // Si presiona Enter, buscar inmediatamente
            if (e.keyCode === 13) {
                performSearch();
                return;
            }

            // Limpiar timeout anterior
            clearTimeout(searchTimeout);

            const query = $(this).val().trim();

            // Si hay menos de 3 caracteres, no buscar
            if (query.length < searchConfig.minChars) {
                return;
            }

            // Buscar después del delay
            searchTimeout = setTimeout(function() {
                performSearch();
            }, searchConfig.delay);
        });

        // Detectar tecla ESC para cerrar
        $searchInput.on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC
                $('.module-fullscreen .module-cancel').click();
            }
        });

        // Auto-focus cuando se abre el buscador
        $('.module-search .module-icon').on('click', function() {
            setTimeout(function() {
                $searchInput.focus();
            }, 300);
        });
    }

    /**
     * Realiza la búsqueda
     */
    function performSearch() {
        const $searchInput = $('.module-fullscreen .form-search input[type="text"]');
        const query = $searchInput.val().trim();

        if (query.length < searchConfig.minChars) {
            alert('Por favor ingresa al menos ' + searchConfig.minChars + ' caracteres');
            return;
        }

        console.log('🔍 Buscando:', query);

        // Obtener idioma actual
        const currentLang = getCurrentLanguage();

        // ACTUALIZADO: Redirigir a php/search.php
        const searchUrl = 'php/search.php?q=' + encodeURIComponent(query) + '&lang=' + currentLang;
        window.location.href = searchUrl;
    }

    /**
     * Obtiene el idioma actual
     */
    function getCurrentLanguage() {
        // Intentar obtener de localStorage
        const savedLang = localStorage.getItem('insalcor-lang');
        if (savedLang) {
            return savedLang;
        }

        // Detectar de la URL
        const urlParams = new URLSearchParams(window.location.search);
        const langParam = urlParams.get('lang');
        if (langParam) {
            return langParam;
        }

        // Detectar si estamos en carpeta /en/
        if (window.location.pathname.includes('/en/')) {
            return 'en';
        }

        // Por defecto: español
        return 'es';
    }

    /**
     * Búsqueda con sugerencias en tiempo real (opcional - avanzado)
     */
    function initLiveSearch() {
        const $searchInput = $('.module-fullscreen .form-search input[type="text"]');
        
        // Crear contenedor para sugerencias si no existe
        if ($('.search-suggestions').length === 0) {
            $searchInput.parent().append('<div class="search-suggestions"></div>');
        }

        const $suggestions = $('.search-suggestions');

        $searchInput.on('keyup', function(e) {
            // Ignorar teclas especiales
            if ([13, 27, 38, 40].includes(e.keyCode)) {
                return;
            }

            clearTimeout(searchTimeout);

            const query = $(this).val().trim();

            if (query.length < searchConfig.minChars) {
                $suggestions.hide().empty();
                return;
            }

            // Buscar después del delay
            searchTimeout = setTimeout(function() {
                fetchSuggestions(query);
            }, searchConfig.delay);
        });

        // Cerrar sugerencias al hacer click fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.form-search').length) {
                $suggestions.hide();
            }
        });
    }

    /**
     * Obtiene sugerencias vía AJAX
     */
    function fetchSuggestions(query) {
        const currentLang = getCurrentLanguage();
        
        $.ajax({
            url: 'php/search.php', // ACTUALIZADO
            method: 'GET',
            data: {
                q: query,
                ajax: 1,
                lang: currentLang
            },
            dataType: 'json',
            success: function(data) {
                displaySuggestions(data);
            },
            error: function(xhr, status, error) {
                console.error('Error en búsqueda:', error);
            }
        });
    }

    /**
     * Muestra las sugerencias
     */
    function displaySuggestions(data) {
        const $suggestions = $('.search-suggestions');
        $suggestions.empty();

        const totalResults = data.total || 0;

        if (totalResults === 0) {
            $suggestions.hide();
            return;
        }

        let html = '<div class="suggestions-wrapper">';

        // Productos
        if (data.products && data.products.length > 0) {
            html += '<div class="suggestion-group">';
            html += '<h4>Productos</h4>';
            
            data.products.slice(0, searchConfig.maxResults).forEach(function(item) {
                html += '<a href="product-single.php?slug=' + item.slug + '&lang=' + getCurrentLanguage() + '" class="suggestion-item">';
                html += '<strong>' + escapeHtml(item.name) + '</strong>';
                if (item.category) {
                    html += '<span class="suggestion-category">' + escapeHtml(item.category) + '</span>';
                }
                html += '</a>';
            });
            
            html += '</div>';
        }

        // Noticias
        if (data.blog && data.blog.length > 0) {
            html += '<div class="suggestion-group">';
            html += '<h4>Noticias</h4>';
            
            data.blog.slice(0, searchConfig.maxResults).forEach(function(item) {
                html += '<a href="blog.php?slug=' + item.slug + '&lang=' + getCurrentLanguage() + '" class="suggestion-item">';
                html += '<strong>' + escapeHtml(item.name) + '</strong>';
                if (item.category) {
                    html += '<span class="suggestion-category">' + escapeHtml(item.category) + '</span>';
                }
                html += '</a>';
            });
            
            html += '</div>';
        }

        html += '<div class="suggestion-footer">';
        html += '<a href="php/search.php?q=' + encodeURIComponent($('.module-fullscreen .form-search input[type="text"]').val()) + '&lang=' + getCurrentLanguage() + '">'; // ACTUALIZADO
        html += 'Ver todos los resultados (' + totalResults + ')';
        html += '</a>';
        html += '</div>';

        html += '</div>';

        $suggestions.html(html).show();
    }

    /**
     * Escapa HTML para prevenir XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Inicialización cuando el DOM esté listo
     */
    $(document).ready(function() {
        initSearch();
        
        // Descomentar para habilitar sugerencias en tiempo real
        // initLiveSearch();
    });

})(jQuery);
