/**
 * Buscador del header (ícono de lupa arriba a la derecha).
 *
 * Busca productos (nombre y descripción) y noticias (título, extracto y
 * contenido), en los dos idiomas. El trabajo pesado lo hace
 * buscar.php: acá sólo se abre/cierra el modal, se muestran las sugerencias
 * en vivo (buscar.php?ajax=1) y se manda el formulario a la página de
 * resultados (buscar.php?q=...).
 *
 * El idioma NO se pasa por la URL: buscar.php lo resuelve con la cookie
 * `insalcor_lang`, la misma que usan el resto de las páginas y assets/js/i18n.js.
 */

(function ($) {
    'use strict';

    var config = {
        endpoint: 'buscar.php',
        minChars: 2,   // igual que BUSCADOR_MIN_CHARS en inc/public.php
        delay: 300,    // ms de espera antes de pedir sugerencias
        maxResults: 5
    };

    var timer = null;
    var pending = null;   // request en vuelo, para poder cancelarla
    var lastQuery = '';

    /** Textos del dropdown; los toma del diccionario si i18n.js ya cargó. */
    function t(key, fallback, vars) {
        var value = fallback;
        // I18n se declara con `const` en i18n.js: es global léxica, no window.I18n.
        if (typeof I18n !== 'undefined' && typeof I18n.t === 'function') {
            var translated = I18n.t(key, vars);
            if (translated && translated !== key) {
                return translated;
            }
        }
        if (vars) {
            Object.keys(vars).forEach(function (k) {
                value = value.replace('{' + k + '}', vars[k]);
            });
        }
        return value;
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : text;
        return div.innerHTML;
    }

    function resultsUrl(query) {
        return config.endpoint + '?q=' + encodeURIComponent(query);
    }

    function init() {
        // Sólo el formulario del modal del header: la página de resultados
        // tiene el suyo, que se manda solo (action="buscar.php").
        var $form = $('.module-fullscreen .form-search');
        var $input = $form.find('input[type="text"]');

        if (!$form.length || !$input.length) {
            return;
        }

        // Que funcione también sin JS / si algo falla más abajo.
        $form.attr('action', config.endpoint).attr('method', 'get');
        $input.attr('name', 'q');

        var $suggestions = $('<div class="search-suggestions"></div>');
        $form.append($suggestions);

        $form.on('submit', function (e) {
            var query = $.trim($input.val());
            if (query.length < config.minChars) {
                e.preventDefault();
                $input.focus();
                return;
            }
            // Deja que el navegador mande el form a buscar.php?q=...
        });

        $input.on('input', function () {
            var query = $.trim($(this).val());
            clearTimeout(timer);

            if (query.length < config.minChars) {
                hideSuggestions();
                return;
            }
            timer = setTimeout(function () {
                fetchSuggestions(query);
            }, config.delay);
        });

        $input.on('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                if ($suggestions.is(':visible')) {
                    hideSuggestions();
                } else {
                    $('.module-fullscreen .module-cancel').trigger('click');
                }
            }
        });

        // Foco automático al abrir el buscador; al cerrarlo, limpiar.
        $('.module-search .module-icon').on('click', function () {
            setTimeout(function () { $input.focus(); }, 300);
        });
        $('.module-fullscreen .module-cancel').on('click', function () {
            hideSuggestions();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.form-search').length) {
                hideSuggestions();
            }
        });

        // Si cambia el idioma, el texto cacheado del dropdown queda viejo.
        $(document).on('i18n:changed', function () {
            hideSuggestions();
            lastQuery = '';
        });
    }

    function hideSuggestions() {
        $('.search-suggestions').hide().empty();
    }

    function fetchSuggestions(query) {
        if (query === lastQuery) {
            return;
        }
        lastQuery = query;

        if (pending) {
            pending.abort();
        }
        pending = $.ajax({
            url: config.endpoint,
            method: 'GET',
            data: { q: query, ajax: '1' },
            dataType: 'json'
        }).done(function (data) {
            // Descarta respuestas viejas que llegaron fuera de orden.
            if (!data || data.q !== query) {
                return;
            }
            render(data, query);
        }).fail(function (xhr, status) {
            if (status !== 'abort') {
                hideSuggestions();
            }
        }).always(function () {
            pending = null;
        });
    }

    function group(title, items) {
        if (!items || !items.length) {
            return '';
        }
        var html = '<div class="suggestion-group"><h4>' + escapeHtml(title) + '</h4>';
        items.slice(0, config.maxResults).forEach(function (item) {
            html += '<a href="' + escapeHtml(item.url) + '" class="suggestion-item">';
            html += '<strong>' + escapeHtml(item.titulo) + '</strong>';
            if (item.categoria) {
                html += '<span class="suggestion-category">' + escapeHtml(item.categoria) + '</span>';
            }
            html += '</a>';
        });
        return html + '</div>';
    }

    function render(data, query) {
        var $suggestions = $('.search-suggestions');
        var total = data.total || 0;

        if (!total) {
            $suggestions
                .html('<div class="no-suggestions"><p>' + escapeHtml(t('search.no_suggestions', 'Sin resultados')) + '</p></div>')
                .show();
            return;
        }

        var html = '<div class="suggestions-wrapper">';
        html += group(t('search.products_title', 'Productos'), data.productos);
        html += group(t('search.news_title', 'Noticias'), data.noticias);
        html += '<div class="suggestion-footer"><a href="' + escapeHtml(resultsUrl(query)) + '">'
            + escapeHtml(t('search.view_all', 'Ver todos los resultados ({n})', { n: total }))
            + '</a></div>';
        html += '</div>';

        $suggestions.html(html).show();
    }

    $(document).ready(init);

})(jQuery);
