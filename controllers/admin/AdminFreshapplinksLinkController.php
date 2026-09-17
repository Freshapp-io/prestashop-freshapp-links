<?php
/**
 * FreshApp Links.
 *
 * @author    FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license   Proprietary - see LICENSE file
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminFreshapplinksLinkController extends ModuleAdminController
{
    /** @var int */
    private $idList = 0;

    protected $position_identifier = 'id_freshapplinks_link';

    public function __construct()
    {
        $this->context = Context::getContext();
        $this->bootstrap = true;

        $this->table = 'freshapplinks_link';
        $this->className = 'FreshapplinksLink';
        $this->identifier = 'id_freshapplinks_link';
        $this->lang = true;
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';

        $this->module = Module::getInstanceByName('freshapplinks');

        parent::__construct();

        $this->idList = (int) Tools::getValue('id_freshapplinks_list');
        if ($this->idList) {
            $this->_where = ' AND a.id_freshapplinks_list = ' . $this->idList;
            $this->context->smarty->assign('id_freshapplinks_list', $this->idList);
        }
    }

    /**
     * init() (pas __construct()) définit self::$currentIndex à partir de la requête — ajouter
     * le paramètre de scope ici, après parent::init(), fait que chaque URL d'action générée
     * (édition, suppression, nouveau, pagination, redirections de soumission) le porte
     * automatiquement. L'ajouter dans le constructeur serait silencieusement effacé puisque
     * init() s'exécute après et le réassigne.
     */
    public function init()
    {
        parent::init();

        if ($this->idList) {
            self::$currentIndex .= '&id_freshapplinks_list=' . $this->idList;
        }
    }

    /** Compatibilité PS9 : l() n'existe plus sur ModuleAdminController. */
    protected function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        return $this->module ? $this->module->l($string) : $string;
    }

    public function initContent()
    {
        if (Tools::isSubmit('ajax')) {
            parent::initContent();

            return;
        }

        if (!$this->idList) {
            $this->errors[] = $this->l('Liste de liens introuvable — retournez à la liste des listes.');
        }

        parent::initContent();
    }

    private function isValidIconUpload(string $tmpPath, string $ext): bool
    {
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            return false !== @getimagesize($tmpPath);
        }
        if ('svg' === $ext) {
            // Lit le fichier entier : un scan tronqué peut manquer du contenu actif au-delà de la limite.
            $content = @file_get_contents($tmpPath);
            if (false === $content || !preg_match('/<svg[\s>]/i', $content)) {
                return false;
            }

            // Reject any active-content vector (script/event handlers/external refs/embedded objects).
            $dangerous = [
                '/<script/i',
                '/<foreignObject/i',
                '/<(iframe|object|embed|use|image|animate|set|handler)\b/i',
                '/\son\w+\s*=/i',               // onload=, onclick=, …
                '/(href|xlink:href)\s*=\s*["\']?\s*javascript:/i',
                '/javascript:/i',
                '/<!ENTITY/i',                  // XXE / entity expansion
            ];
            foreach ($dangerous as $pattern) {
                if (preg_match($pattern, $content)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    private function sanitizeHexColor(string $raw): string
    {
        $raw = trim($raw);

        return preg_match('/^#[0-9a-fA-F]{3,6}$/', $raw) ? $raw : '';
    }

    private function sanitizeCssLength(string $raw): string
    {
        $raw = trim($raw);

        return preg_match('/^\d*\.?\d+(rem|px|em|%)$/', $raw) ? $raw : '';
    }

    /* ------------------------------------------------------------------ */
    /* Recherche AJAX (autocomplete produit / catégorie / page CMS) */
    /* ------------------------------------------------------------------ */

    public function ajaxProcessSearchTargets()
    {
        $type = (string) Tools::getValue('target_type');
        $q = trim((string) Tools::getValue('q', ''));
        $idLang = (int) $this->context->language->id;
        $results = [];

        if ('' !== $q) {
            $like = '%' . pSQL($q) . '%';
            switch ($type) {
                case 'product':
                    $results = Db::getInstance()->executeS(
                        'SELECT p.id_product AS id, pl.name AS name
                         FROM `' . _DB_PREFIX_ . 'product` p
                         INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                            ON pl.id_product = p.id_product AND pl.id_lang = ' . $idLang . '
                         WHERE pl.name LIKE \'' . $like . '\'
                         ORDER BY pl.name ASC LIMIT 20',
                    );
                    break;

                case 'category':
                    $results = Db::getInstance()->executeS(
                        'SELECT c.id_category AS id, cl.name AS name
                         FROM `' . _DB_PREFIX_ . 'category` c
                         INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                            ON cl.id_category = c.id_category AND cl.id_lang = ' . $idLang . '
                         WHERE cl.name LIKE \'' . $like . '\'
                         ORDER BY cl.name ASC LIMIT 20',
                    );
                    break;

                case 'cms':
                    $results = Db::getInstance()->executeS(
                        'SELECT c.id_cms AS id, cl.meta_title AS name
                         FROM `' . _DB_PREFIX_ . 'cms` c
                         INNER JOIN `' . _DB_PREFIX_ . 'cms_lang` cl
                            ON cl.id_cms = c.id_cms AND cl.id_lang = ' . $idLang . '
                         WHERE c.active = 1 AND cl.meta_title LIKE \'' . $like . '\'
                         ORDER BY cl.meta_title ASC LIMIT 20',
                    );
                    break;
            }
        }

        header('Content-Type: application/json');
        exit(json_encode(['results' => $results ?: []]));
    }

    /** Nom affichable d'une cible déjà sélectionnée, pour préremplir le champ de recherche. */
    private function resolveTargetName(string $type, int $targetId, int $idLang): string
    {
        if ($targetId <= 0) {
            return '';
        }

        switch ($type) {
            case 'product':
                return (string) Db::getInstance()->getValue(
                    'SELECT name FROM `' . _DB_PREFIX_ . 'product_lang` WHERE id_product = ' . $targetId . ' AND id_lang = ' . $idLang,
                );
            case 'category':
                return (string) Db::getInstance()->getValue(
                    'SELECT name FROM `' . _DB_PREFIX_ . 'category_lang` WHERE id_category = ' . $targetId . ' AND id_lang = ' . $idLang,
                );
            case 'cms':
                return (string) Db::getInstance()->getValue(
                    'SELECT meta_title FROM `' . _DB_PREFIX_ . 'cms_lang` WHERE id_cms = ' . $targetId . ' AND id_lang = ' . $idLang,
                );
            default:
                return '';
        }
    }

    /* ------------------------------------------------------------------ */
    /* Listing */
    /* ------------------------------------------------------------------ */

    public function renderList()
    {
        $this->fields_list = [
            'id_freshapplinks_link' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'label' => ['title' => $this->l('Libellé')],
            'target_type' => ['title' => $this->l('Cible')],
            'position' => ['title' => $this->l('Position'), 'filter_key' => 'a!position', 'align' => 'center', 'class' => 'fixed-width-sm', 'position' => 'position'],
            'active' => ['title' => $this->l('Actif'), 'type' => 'bool', 'active' => 'active'],
        ];
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->toolbar_btn['new'] = [
            'href' => self::$currentIndex . '&addfreshapplinks_link&token=' . $this->token,
            'desc' => $this->l('Ajouter un lien'),
        ];

        $backUrl = $this->context->link->getAdminLink('AdminFreshapplinks');
        $back = '<p style="margin:10px 0"><a href="' . $backUrl . '" class="btn btn-default"><i class="icon-arrow-left"></i> ' . $this->l('Retour aux listes') . '</a></p>';

        return $back . parent::renderList() . $this->renderDragDropScript();
    }

    /**
     * Le drag & drop natif de HelperList de PS9 ('position' => 'position') ne persiste pas sur
     * cette install par défaut. La page charge aussi automatiquement le `js/admin/dnd.js` du core
     * PrestaShop (déclenché par `orderBy=position`), qui auto-initialise jQuery `tableDnD` sur
     * cette même table avec `dragHandle: "dragHandle"` (lié à la cellule icône
     * `<td class="dragHandle">`, pas au `<tr>`) et son propre `onDrop` — mais cet `onDrop` du core
     * poste un format de payload (`action=updatePositions&id=X&way=Y`) que notre
     * `ajaxProcessUpdatePositions()` surchargé ci-dessous ne comprend pas, donc il ne fait
     * silencieusement rien. Plutôt que de lutter contre `tableDnD` (le drag-and-drop HTML5 natif
     * sur `<tr>` a été testé et ne déclenche jamais `dragstart` sur cette install — un autre
     * script intercepte le mousedown en premier), on RÉUTILISE le plugin : on purge le listener
     * du core de l'élément réel auquel il est lié (`.dragHandle`, pas la ligne) et on réinitialise
     * avec notre propre `onDrop` qui sauvegarde le nouvel ordre. `dragHandle: "dragHandle"` sur
     * NOTRE réinit aussi, pour qu'un clic tombant sur le glyphe de l'icône lui-même (pas juste le
     * fond du `<td>`) démarre quand même le drag. Voir
     * `AdminThemeconfigurationController::renderDragDropScript()` pour l'implémentation de
     * référence dont ceci a été porté (vérifiée de bout en bout via une vraie session BO
     * Playwright). Scopé à la liste courante via le propre filtre id_freshapplinks_list de
     * ajaxProcessUpdatePositions().
     */
    private function renderDragDropScript(): string
    {
        $ajaxUrl = self::$currentIndex . '&token=' . $this->token . '&ajax=1&action=UpdatePositions';

        return '
        <script>
        (function(){
          var TAG = "[FLL-DnD]";
          function L(){ try { console.log.apply(console, [TAG].concat([].slice.call(arguments))); } catch(e){} }
          var ajaxUrl = ' . json_encode($ajaxUrl) . ';

          function ridOf(tr){
            // Ligne HelperList native : id="tr_{groupe}_{id}_{position}" → l\'id de
            // l\'enregistrement est l\'avant-dernier segment (le dernier = la position).
            var parts = (tr.id || "").split("_");
            return parts[parts.length - 2];
          }

          function savePositions(tbody){
            var ids = [];
            tbody.querySelectorAll("tr").forEach(function(tr){
              var rid = ridOf(tr);
              if (rid) ids.push(rid);
            });
            L("savePositions -> ids (nouvel ordre) =", ids.join(","));
            var body = new URLSearchParams();
            body.set("positions", ids.join(","));
            fetch(ajaxUrl, { method: "POST", body: body, credentials: "same-origin" })
              .then(function(r){ L("réponse HTTP", r.status); return r.text(); })
              .then(function(t){ L("corps réponse =", t); })
              .catch(function(err){ L("ERREUR fetch =", err); });
          }

          function setup(){
            var table = document.getElementById("table-' . $this->table . '");
            var tbody = table ? table.querySelector("tbody") : null;
            if (!table) { L("ERREUR: table introuvable"); return; }
            if (!tbody) { L("ERREUR: tbody introuvable"); return; }
            L("setup, nb lignes =", tbody.querySelectorAll("tr").length, "| ajaxUrl =", ajaxUrl);

            var $ = window.jQuery;

            if ($ && $.fn && typeof $.fn.tableDnD === "function") {
              $(table).find("tbody tr").off("mousedown");
              $(table).find(".dragHandle").off("mousedown");
              $(table).tableDnD({
                dragHandle: "dragHandle",
                onDrop: function(t){
                  L("onDrop tableDnD");
                  savePositions(t.querySelector("tbody") || tbody);
                }
              });
              L("OK: tableDnD ré-initialisé avec onDrop -> sauvegarde (dragHandle core purgé)");
              return;
            }

            // Repli si tableDnD indisponible : drag & drop HTML5 natif.
            L("tableDnD indisponible -> repli HTML5 natif");
            var dragging = null;
            tbody.querySelectorAll("tr").forEach(function(row){
              row.setAttribute("draggable", "true");
              row.style.cursor = "move";
              row.addEventListener("dragstart", function(e){
                dragging = row; row.style.opacity = "0.4"; L("dragstart", row.id);
                if (e.dataTransfer) { e.dataTransfer.effectAllowed = "move"; try { e.dataTransfer.setData("text/plain", row.id || ""); } catch (err) {} }
              });
              row.addEventListener("dragend", function(){ row.style.opacity = ""; dragging = null; });
              row.addEventListener("dragover", function(e){
                e.preventDefault();
                if (!dragging || dragging === row) return;
                var rect = row.getBoundingClientRect();
                var before = (e.clientY - rect.top) < rect.height / 2;
                tbody.insertBefore(dragging, before ? row : row.nextSibling);
              });
              row.addEventListener("drop", function(e){ e.preventDefault(); L("drop sur", row.id); savePositions(tbody); });
            });
            L("repli HTML5 initialisé");
          }

          // Doit s\'exécuter APRÈS l\'init de tableDnD (qui tourne au DOM ready) pour pouvoir
          // le reconfigurer : on attend l\'événement load (ou immédiat si déjà chargé).
          if (document.readyState === "complete") { setup(); }
          else { window.addEventListener("load", setup); }
        }());
        </script>';
    }

    public function ajaxProcessUpdatePositions()
    {
        $ids = array_filter(array_map('intval', explode(',', (string) Tools::getValue('positions', ''))));
        $db = Db::getInstance();
        foreach (array_values($ids) as $pos => $id) {
            $db->update(
                $this->table,
                ['position' => $pos],
                '`' . bqSQL($this->identifier) . '` = ' . (int) $id . ' AND `id_freshapplinks_list` = ' . (int) $this->idList,
            );
        }

        header('Content-Type: application/json');
        exit(json_encode(['ok' => true, 'count' => count($ids)]));
    }

    /* ------------------------------------------------------------------ */
    /* Formulaire */
    /* ------------------------------------------------------------------ */

    public function renderForm()
    {
        $id = (int) Tools::getValue($this->identifier);
        $link = $id ? new FreshapplinksLink($id) : null;
        $idLang = (int) $this->context->language->id;

        $ov = [];
        if ($link && $link->style_overrides) {
            $decoded = json_decode($link->style_overrides, true);
            if (is_array($decoded)) {
                $ov = $decoded;
            }
        }
        $ovGet = static function (array $ov, string $key): string {
            return isset($ov[$key]) ? (string) $ov[$key] : '';
        };

        $targetType = $link->target_type ?? 'custom';
        $targetId = (int) ($link->target_id ?? 0);
        $targetName = $this->resolveTargetName($targetType, $targetId, $idLang);

        $ajaxUrl = self::$currentIndex . '&token=' . $this->token . '&ajax=1&action=SearchTargets';

        $targetTypeSelect = '<select name="target_type" id="fpl-target-type" class="form-control">';
        foreach (['custom' => $this->l('URL libre'), 'product' => $this->l('Produit'), 'category' => $this->l('Catégorie'), 'cms' => $this->l('Page CMS')] as $val => $lbl) {
            $targetTypeSelect .= '<option value="' . $val . '"' . ($val === $targetType ? ' selected' : '') . '>' . $lbl . '</option>';
        }
        $targetTypeSelect .= '</select>';

        $customUrlFields = '';
        foreach (Language::getLanguages(false) as $language) {
            $langId = (int) $language['id_lang'];
            $val = '';
            if ($link && $link->id) {
                $row = Db::getInstance()->getRow(
                    'SELECT custom_url FROM `' . _DB_PREFIX_ . 'freshapplinks_link_lang`
                     WHERE id_freshapplinks_link = ' . (int) $link->id . ' AND id_lang = ' . $langId,
                );
                $val = $row ? (string) $row['custom_url'] : '';
            }
            $customUrlFields .= '
            <div class="fpl-custom-url-field" style="margin-bottom:6px">
              <span class="input-group-addon" style="display:inline-block;width:40px">' . strtoupper($language['iso_code']) . '</span>
              <input type="text" name="custom_url_' . $langId . '" class="form-control" style="display:inline-block;width:calc(100% - 50px)" placeholder="https://..." value="' . htmlspecialchars($val, ENT_QUOTES) . '">
            </div>';
        }

        $targetSearchBlock = '
        <div class="form-group" id="fpl-target-search-wrap" style="' . ('custom' === $targetType ? 'display:none' : '') . '">
          <label class="control-label col-lg-3">' . $this->l('Rechercher la cible') . '</label>
          <div class="col-lg-9" style="position:relative">
            <input type="text" id="fpl-target-search" class="form-control" autocomplete="off"
                   placeholder="' . $this->l('Tapez pour rechercher...') . '" value="' . htmlspecialchars($targetName, ENT_QUOTES) . '">
            <input type="hidden" name="target_id" id="fpl-target-id" value="' . $targetId . '">
            <div id="fpl-target-results" style="position:absolute;z-index:100;background:#fff;border:1px solid #ddd;width:100%;max-height:220px;overflow:auto;display:none"></div>
            <p class="help-block">' . $this->l('Sélectionnez un élément dans la liste proposée pendant la saisie.') . '</p>
          </div>
        </div>
        <div class="form-group" id="fpl-target-custom-wrap" style="' . ('custom' !== $targetType ? 'display:none' : '') . '">
          <label class="control-label col-lg-3">' . $this->l('URL (par langue)') . '</label>
          <div class="col-lg-9">' . $customUrlFields . '</div>
        </div>
        <script>
        (function(){
          var typeSel = document.getElementById("fpl-target-type");
          var searchWrap = document.getElementById("fpl-target-search-wrap");
          var customWrap = document.getElementById("fpl-target-custom-wrap");
          var searchInput = document.getElementById("fpl-target-search");
          var idInput = document.getElementById("fpl-target-id");
          var results = document.getElementById("fpl-target-results");
          if (!typeSel) return;

          typeSel.addEventListener("change", function(){
            if (typeSel.value === "custom") {
              searchWrap.style.display = "none";
              customWrap.style.display = "";
            } else {
              searchWrap.style.display = "";
              customWrap.style.display = "none";
              idInput.value = "";
              searchInput.value = "";
            }
          });

          var timer = null;
          searchInput.addEventListener("input", function(){
            idInput.value = "";
            clearTimeout(timer);
            var q = searchInput.value.trim();
            if (q.length < 2) { results.style.display = "none"; return; }
            timer = setTimeout(function(){
              fetch("' . $ajaxUrl . '&target_type=" + encodeURIComponent(typeSel.value) + "&q=" + encodeURIComponent(q))
                .then(function(r){ return r.json(); })
                .then(function(data){
                  results.innerHTML = "";
                  if (!data.results || !data.results.length) { results.style.display = "none"; return; }
                  data.results.forEach(function(item){
                    var opt = document.createElement("div");
                    opt.textContent = item.name;
                    opt.style.padding = "6px 10px";
                    opt.style.cursor = "pointer";
                    opt.addEventListener("mouseenter", function(){ opt.style.background = "#f5f5f5"; });
                    opt.addEventListener("mouseleave", function(){ opt.style.background = "#fff"; });
                    opt.addEventListener("click", function(){
                      searchInput.value = item.name;
                      idInput.value = item.id;
                      results.style.display = "none";
                    });
                    results.appendChild(opt);
                  });
                  results.style.display = "";
                });
            }, 250);
          });

          document.addEventListener("click", function(e){
            if (e.target !== searchInput) { results.style.display = "none"; }
          });
        }());
        </script>';

        $hintLength = $this->l('Valeur CSS avec unité : rem, px, em ou %. Ex : 1rem, 16px.');
        $hintHex = $this->l('Format hexadécimal #RRGGBB ou #RGB. Vide = hérite du style de la liste.');
        $hintIconImage = $this->l('Formats acceptés : SVG, PNG, JPG, WEBP. Une image prime sur une icône du sprite si les deux sont renseignées.');

        $fwSel = static function (string $cur): string {
            $opts = '<option value=""' . ('' === $cur ? ' selected' : '') . '>— hérite de la liste —</option>';
            foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $w) {
                $opts .= '<option value="' . $w . '"' . ((string) $w === $cur ? ' selected' : '') . '>' . $w . '</option>';
            }

            return $opts;
        };
        $ttSel = static function (string $cur): string {
            $opts = '<option value=""' . ('' === $cur ? ' selected' : '') . '>— hérite de la liste —</option>';
            foreach (['none' => 'None', 'uppercase' => 'UPPERCASE', 'lowercase' => 'lowercase', 'capitalize' => 'Capitalize'] as $val => $label) {
                $opts .= '<option value="' . $val . '"' . ($val === $cur ? ' selected' : '') . '>' . $label . '</option>';
            }

            return $opts;
        };
        $colorField = static function (string $name, string $label, string $cur) use ($hintHex): string {
            return '
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $label . '</label>
              <div style="display:flex;align-items:center;gap:8px">
                <input type="color" class="fpl-ov-color-input" data-pair="' . $name . '_text"
                       value="' . htmlspecialchars($cur ?: '#000000', ENT_QUOTES) . '"
                       style="width:48px;height:34px;padding:2px 4px;cursor:pointer">
                <input type="text" class="form-control" name="' . $name . '_text"
                       value="' . htmlspecialchars($cur, ENT_QUOTES) . '"
                       placeholder="vide = liste" style="width:120px" maxlength="7">
              </div>
              <p class="help-block">' . $hintHex . '</p>
            </div>
          </div>';
        };

        $iconImage = $ovGet($ov, 'icon_image') ?: (string) ($link->icon_image ?? '');

        $iconBlock = '
        <hr>
        <p class="text-muted"><strong>' . $this->l('Icône') . '</strong></p>
        <div class="row">
          <div class="col-sm-4">
            <div class="form-group">
              <label>' . $this->l('Icône — nom dans le jeu embarqué') . '</label>
              <input type="text" name="icon_class" class="form-control" list="fa-module-icons" placeholder="icon-arrow-right" value="' . htmlspecialchars($link->icon_class ?? '', ENT_QUOTES) . '">
              ' . $this->iconDatalist() . '
              <p class="help-block">' . $this->l('Icônes fournies par le module, indépendantes du thème : commencez à taper pour voir la liste.') . '</p>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="form-group">
              <label>' . $this->l('Icône — image') . '</label>
              ' . ($iconImage ? '<p><img src="' . htmlspecialchars($this->module->_path . $iconImage, ENT_QUOTES) . '" alt="" style="max-height:24px"></p>' : '') . '
              <input type="file" name="icon_image" accept=".svg,.png,.jpg,.jpeg,.webp">
              <p class="help-block">' . $hintIconImage . '</p>
            </div>
          </div>
        </div>
        <p class="text-muted" style="margin-top:15px"><strong>' . $this->l('Style de ce lien (vide = hérite du style par défaut de la liste)') . '</strong></p>
        <div class="row">
          ' . $colorField('icon_color', $this->l('Couleur icône'), $ovGet($ov, 'icon_color')) . '
          ' . $colorField('link_color', $this->l('Couleur lien'), $ovGet($ov, 'link_color')) . '
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $this->l('Graisse') . '</label>
              <select name="font_weight" class="form-control">' . $fwSel($ovGet($ov, 'font_weight')) . '</select>
            </div>
          </div>
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $this->l('Transformation') . '</label>
              <select name="text_transform" class="form-control">' . $ttSel($ovGet($ov, 'text_transform')) . '</select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $this->l('Famille de police') . '</label>
              <input type="text" name="font_family" class="form-control" placeholder="' . $this->l('vide = liste') . '" value="' . htmlspecialchars($ovGet($ov, 'font_family'), ENT_QUOTES) . '">
            </div>
          </div>
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $this->l('Taille de police') . '</label>
              <input type="text" name="font_size" class="form-control" placeholder="' . $this->l('vide = liste') . '" value="' . htmlspecialchars($ovGet($ov, 'font_size'), ENT_QUOTES) . '">
              <p class="help-block">' . $hintLength . '</p>
            </div>
          </div>
        </div>
        <script>
        (function(){
          document.querySelectorAll(".fpl-ov-color-input").forEach(function(color){
            var text = document.querySelector(\'[name="\' + color.getAttribute("data-pair") + \'"]\');
            if (!text) return;
            color.addEventListener("input", function(){ text.value = color.value; });
            text.addEventListener("input", function(){
              if (/^#[0-9a-fA-F]{3,6}$/.test(text.value)) { color.value = text.value; }
            });
          });
        }());
        </script>';

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Lien'),
                'icon' => 'icon-link',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Libellé'),
                    'name' => 'label',
                    'lang' => true,
                    'required' => true,
                ],
                [
                    'type' => 'html',
                    'name' => 'fpl_target_type_html',
                    'html_content' => '
        <div class="form-group">
          <label class="control-label col-lg-3">' . $this->l('Type de cible') . '</label>
          <div class="col-lg-9">' . $targetTypeSelect . '</div>
        </div>',
                ],
                [
                    'type' => 'html',
                    'name' => 'fpl_target_search_html',
                    'html_content' => $targetSearchBlock,
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Ouverture du lien'),
                    'name' => 'link_target',
                    'options' => [
                        'query' => [
                            ['id' => '_self', 'name' => $this->l('Même onglet')],
                            ['id' => '_blank', 'name' => $this->l('Nouvel onglet')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'html',
                    'name' => 'fpl_icon_html',
                    'html_content' => $iconBlock,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Position'),
                    'name' => 'position',
                    'class' => 'fixed-width-xs',
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Actif'),
                    'name' => 'active',
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')],
                    ],
                ],
                [
                    'type' => 'hidden',
                    'name' => 'id_freshapplinks_list',
                ],
            ],
            'submit' => [
                'title' => $this->l('Enregistrer'),
            ],
        ];

        $this->fields_value['id_freshapplinks_list'] = $this->idList;

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table) || Tools::isSubmit('submitUpdate' . $this->table)) {
            $id = (int) Tools::getValue($this->identifier);
            $link = $id ? $this->loadObject() : new FreshapplinksLink();

            $this->copyFromPost($link, $this->table);

            $link->id_freshapplinks_list = (int) Tools::getValue('id_freshapplinks_list', $this->idList);

            $type = (string) Tools::getValue('target_type', 'custom');
            $link->target_type = in_array($type, ['product', 'category', 'cms', 'custom'], true) ? $type : 'custom';

            if ('custom' === $link->target_type) {
                $link->target_id = null;
            } else {
                $targetId = (int) Tools::getValue('target_id', 0);
                if ($targetId <= 0) {
                    $this->errors[] = $this->l('Veuillez sélectionner une cible dans les résultats de recherche.');
                }
                $link->target_id = $targetId;
            }

            $linkTarget = (string) Tools::getValue('link_target', '_self');
            $link->link_target = '_blank' === $linkTarget ? '_blank' : '_self';

            $link->icon_class = trim((string) Tools::getValue('icon_class', ''));

            $uploadDir = _PS_MODULE_DIR_ . $this->module->name . '/views/img/link-icons/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
                @chmod($uploadDir, 0775);
            }
            $link->icon_image = (string) ($link->icon_image ?? '');
            if (isset($_FILES['icon_image']) && is_uploaded_file($_FILES['icon_image']['tmp_name'])) {
                $ext = strtolower(pathinfo($_FILES['icon_image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['svg', 'png', 'jpg', 'jpeg', 'webp'], true) || !$this->isValidIconUpload($_FILES['icon_image']['tmp_name'], $ext)) {
                    $this->errors[] = $this->l('Fichier icône invalide (svg, png, jpg, webp autorisés — le contenu est vérifié, pas seulement l\'extension).');
                } else {
                    $filename = uniqid('link_icon_', true) . '.' . $ext;
                    if (@move_uploaded_file($_FILES['icon_image']['tmp_name'], $uploadDir . $filename)) {
                        $link->icon_image = 'views/img/link-icons/' . $filename;
                    } else {
                        $this->errors[] = $this->l('Échec de l\'upload de l\'icône.');
                    }
                }
            }

            $ovData = [];
            foreach (['icon_color', 'link_color'] as $colorKey) {
                $raw = trim((string) Tools::getValue($colorKey . '_text', ''));
                if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $raw)) {
                    $ovData[$colorKey] = $raw;
                }
            }
            $fw = (string) Tools::getValue('font_weight', '');
            if (in_array($fw, ['100', '200', '300', '400', '500', '600', '700', '800', '900'], true)) {
                $ovData['font_weight'] = $fw;
            }
            $tt = (string) Tools::getValue('text_transform', '');
            if (in_array($tt, ['none', 'uppercase', 'lowercase', 'capitalize'], true)) {
                $ovData['text_transform'] = $tt;
            }
            $fontFamily = trim((string) Tools::getValue('font_family', ''));
            if ('' !== $fontFamily) {
                $ovData['font_family'] = $fontFamily;
            }
            $fontSize = $this->sanitizeCssLength((string) Tools::getValue('font_size', ''));
            if ('' !== $fontSize) {
                $ovData['font_size'] = $fontSize;
            }
            $link->style_overrides = empty($ovData) ? '' : json_encode($ovData);

            // custom_url est multilangue : copyFromPost() ne gère que les champs déclarés dans
            // fields_form (target_type/target_id ne le sont pas) — on lit/valide nous-mêmes.
            foreach (Language::getLanguages(false) as $language) {
                $idLang = (int) $language['id_lang'];
                $url = trim((string) Tools::getValue('custom_url_' . $idLang, ''));
                if ('' !== $url && !Validate::isUrl($url) && '/' !== $url[0]) {
                    $this->errors[] = sprintf($this->l('URL invalide pour la langue %s.'), $language['iso_code']);
                }
                $link->custom_url[$idLang] = $url;
            }

            if (empty($this->errors)) {
                $ok = $link->id ? $link->update() : $link->add();
                if ($ok) {
                    Tools::redirectAdmin(self::$currentIndex . '&conf=4&token=' . $this->token);
                }
                $this->errors[] = $this->l('Erreur lors de l\'enregistrement.');
            }

            return;
        }

        return parent::postProcess();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS('modules/' . $this->module->name . '/views/css/back.css');
    }

    /**
     * Suggestions du champ « icône » : les identifiants du sprite embarqué dans le module.
     */
    private function iconDatalist(): string
    {
        $options = '';
        foreach ($this->module->getIconNames() as $name) {
            $options .= '<option value="' . htmlspecialchars($name, ENT_QUOTES) . '"></option>';
        }

        return '<datalist id="fa-module-icons">' . $options . '</datalist>';
    }
}
