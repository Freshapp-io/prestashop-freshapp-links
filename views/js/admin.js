/**
 * FreshApp Links
 *
 * @author    FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license   Proprietary - see LICENSE file
 *
 * Comportements du back-office :
 * - glisser-déposer des listes et des liens (ordre enregistré en AJAX) ;
 * - synchronisation sélecteur de couleur / champ hexadécimal ;
 * - recherche de la cible d'un lien (produit, catégorie, page CMS).
 *
 * Chargé dans le <head> du back-office : tout démarre après le chargement du DOM.
 */
(function () {
  /* ------------------------------------------------------------------ */
  /* Glisser-déposer                                                     */
  /* ------------------------------------------------------------------ */

  /*
   * Le drag & drop natif de HelperList ('position' => 'position') ne persiste pas : le core
   * initialise jQuery tableDnD avec un onDrop dont le format de requête ne correspond pas à
   * ajaxProcessUpdatePositions() du module. On réutilise le plugin en purgeant l'écouteur du
   * core (lié à .dragHandle) et en le réinitialisant avec notre onDrop. À défaut de tableDnD,
   * repli sur le drag & drop HTML5.
   */
  function setupDnd(config) {
    var TAG = '[' + (config.getAttribute('data-tag') || 'FL-DnD') + ']';
    var ajaxUrl = config.getAttribute('data-ajax-url');
    var table = document.getElementById(config.getAttribute('data-table-id'));
    var tbody = table ? table.querySelector('tbody') : null;
    function L() { try { console.log.apply(console, [TAG].concat([].slice.call(arguments))); } catch (e) {} }
    if (!table || !tbody) {
      return;
    }

    function ridOf(tr) {
      // Ligne HelperList : id="tr_{groupe}_{id}_{position}" → l'id est l'avant-dernier segment.
      var parts = (tr.id || '').split('_');
      return parts[parts.length - 2];
    }

    function savePositions(body) {
      var ids = [];
      body.querySelectorAll('tr').forEach(function (tr) {
        var rid = ridOf(tr);
        if (rid) {
          ids.push(rid);
        }
      });
      var data = new URLSearchParams();
      data.set('positions', ids.join(','));
      fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
        .then(function (r) { L('réponse HTTP', r.status); })
        .catch(function (err) { L('ERREUR fetch =', err); });
    }

    var $ = window.jQuery;
    if ($ && $.fn && typeof $.fn.tableDnD === 'function') {
      $(table).find('tbody tr').off('mousedown');
      $(table).find('.dragHandle').off('mousedown');
      $(table).tableDnD({
        dragHandle: 'dragHandle',
        onDrop: function (t) { savePositions(t.querySelector('tbody') || tbody); }
      });
      return;
    }

    var dragging = null;
    tbody.querySelectorAll('tr').forEach(function (row) {
      row.setAttribute('draggable', 'true');
      row.style.cursor = 'move';
      row.addEventListener('dragstart', function (e) {
        dragging = row;
        row.style.opacity = '0.4';
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = 'move';
          try { e.dataTransfer.setData('text/plain', row.id || ''); } catch (err) {}
        }
      });
      row.addEventListener('dragend', function () { row.style.opacity = ''; dragging = null; });
      row.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!dragging || dragging === row) {
          return;
        }
        var rect = row.getBoundingClientRect();
        var before = (e.clientY - rect.top) < rect.height / 2;
        tbody.insertBefore(dragging, before ? row : row.nextSibling);
      });
      row.addEventListener('drop', function (e) { e.preventDefault(); savePositions(tbody); });
    });
  }

  /* ------------------------------------------------------------------ */
  /* Couleurs                                                            */
  /* ------------------------------------------------------------------ */

  function setupColors() {
    document.querySelectorAll('.fpl-color-input').forEach(function (color) {
      var text = document.querySelector('[name="' + color.getAttribute('data-target') + '"]');
      if (!text) {
        return;
      }
      color.addEventListener('input', function () { text.value = color.value; });
      text.addEventListener('input', function () {
        if (/^#[0-9a-fA-F]{3,6}$/.test(text.value)) {
          color.value = text.value;
        }
      });
    });
  }

  /* ------------------------------------------------------------------ */
  /* Cible d'un lien                                                     */
  /* ------------------------------------------------------------------ */

  function setupTargetSearch() {
    var typeSel = document.getElementById('fpl-target-type');
    var searchWrap = document.getElementById('fpl-target-search-wrap');
    var customWrap = document.getElementById('fpl-target-custom-wrap');
    var searchInput = document.getElementById('fpl-target-search');
    var idInput = document.getElementById('fpl-target-id');
    var results = document.getElementById('fpl-target-results');
    if (!typeSel || !searchInput) {
      return;
    }
    var ajaxUrl = searchInput.getAttribute('data-ajax-url');

    typeSel.addEventListener('change', function () {
      if (typeSel.value === 'custom') {
        searchWrap.style.display = 'none';
        customWrap.style.display = '';
      } else {
        searchWrap.style.display = '';
        customWrap.style.display = 'none';
        idInput.value = '';
        searchInput.value = '';
      }
    });

    var timer = null;
    searchInput.addEventListener('input', function () {
      idInput.value = '';
      clearTimeout(timer);
      var q = searchInput.value.trim();
      if (q.length < 2) {
        results.style.display = 'none';
        return;
      }
      timer = setTimeout(function () {
        fetch(ajaxUrl + '&target_type=' + encodeURIComponent(typeSel.value) + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            results.textContent = '';
            if (!data.results || !data.results.length) {
              results.style.display = 'none';
              return;
            }
            data.results.forEach(function (item) {
              var opt = document.createElement('div');
              opt.textContent = item.name;
              opt.style.padding = '6px 10px';
              opt.style.cursor = 'pointer';
              opt.addEventListener('mouseenter', function () { opt.style.background = '#f5f5f5'; });
              opt.addEventListener('mouseleave', function () { opt.style.background = '#fff'; });
              opt.addEventListener('click', function () {
                searchInput.value = item.name;
                idInput.value = item.id;
                results.style.display = 'none';
              });
              results.appendChild(opt);
            });
            results.style.display = '';
          });
      }, 250);
    });

    document.addEventListener('click', function (e) {
      if (e.target !== searchInput) {
        results.style.display = 'none';
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    setupColors();
    setupTargetSearch();
  });

  // tableDnD est initialisé par le core au DOM ready : on le reconfigure après « load ».
  function initDnd() {
    document.querySelectorAll('.js-fpl-dnd').forEach(setupDnd);
  }
  if (document.readyState === 'complete') {
    initDnd();
  } else {
    window.addEventListener('load', initDnd);
  }
})();
