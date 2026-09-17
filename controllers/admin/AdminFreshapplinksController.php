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

class AdminFreshapplinksController extends ModuleAdminController
{
    protected $position_identifier = 'id_freshapplinks_list';

    public function __construct()
    {
        $this->context = Context::getContext();
        $this->bootstrap = true;

        $this->table = 'freshapplinks_list';
        $this->className = 'FreshapplinksList';
        $this->identifier = 'id_freshapplinks_list';
        $this->lang = true;
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';

        $this->module = Module::getInstanceByName('freshapplinks');

        parent::__construct();
    }

    /** PS9 compatibility: l() no longer exists on ModuleAdminController. */
    protected function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        return $this->module ? $this->module->l($string) : $string;
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

    private function fontWeightOptions(string $cur): string
    {
        $opts = '<option value=""' . ('' === $cur ? ' selected' : '') . '>— ' . $this->l('normal (400)') . ' —</option>';
        foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $w) {
            $opts .= '<option value="' . $w . '"' . ((string) $w === $cur ? ' selected' : '') . '>' . $w . '</option>';
        }

        return $opts;
    }

    private function textTransformOptions(string $cur): string
    {
        $opts = '<option value=""' . ('' === $cur ? ' selected' : '') . '>— ' . $this->l('aucune') . ' —</option>';
        foreach (['none' => 'None', 'uppercase' => 'UPPERCASE', 'lowercase' => 'lowercase', 'capitalize' => 'Capitalize'] as $val => $label) {
            $opts .= '<option value="' . $val . '"' . ($val === $cur ? ' selected' : '') . '>' . $label . '</option>';
        }

        return $opts;
    }

    public function renderList()
    {
        // Nombre de liens par liste, pour la colonne "Liens" (sous-requête, comme un JOIN classique).
        $this->_select = '(SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'freshapplinks_link` fl
                            WHERE fl.id_freshapplinks_list = a.id_freshapplinks_list) AS links_count';

        $this->fields_list = [
            'id_freshapplinks_list' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name' => ['title' => $this->l('Nom')],
            'hooks' => ['title' => $this->l('Hooks'), 'callback' => 'renderHooksList'],
            'links_count' => ['title' => $this->l('Liens'), 'align' => 'center', 'orderby' => false, 'search' => false, 'callback' => 'renderLinksColumn'],
            'position' => ['title' => $this->l('Position'), 'filter_key' => 'a!position', 'align' => 'center', 'class' => 'fixed-width-sm', 'position' => 'position'],
            'active' => ['title' => $this->l('Actif'), 'type' => 'bool', 'active' => 'active'],
        ];
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->toolbar_btn['new'] = [
            'href' => self::$currentIndex . '&addfreshapplinks_list&token=' . $this->token,
            'desc' => $this->l('Ajouter une liste de liens'),
        ];

        return $this->renderMenuVisibilityToggle() . parent::renderList() . $this->renderDragDropScript();
    }

    /**
     * "Afficher dans le menu du backoffice" — le tab est toujours créé (voir installTabs()), mais
     * masqué du menu par défaut (Tab::active = 0). Reste accessible via "Configurer" tant que
     * l'employé a les droits, quel que soit cet état — voir Tab::checkTabRights(). Ne pilote que
     * le tab parent (liste) : le masquer masque aussi "Liens" (son enfant) de l'arbre du menu.
     */
    private function renderMenuVisibilityToggle(): string
    {
        $idTab = (int) Tab::getIdFromClassName('AdminFreshapplinks');
        $checked = $idTab && (new Tab($idTab))->active;

        return '
        <div class="panel" style="display:flex;align-items:center;justify-content:flex-end;gap:12px;padding:12px 20px;">
          <span>' . $this->l('Afficher dans le menu du backoffice') . '</span>
          <form method="post" style="margin:0">
            <input type="hidden" name="submitFreshappBoMenuVisibility" value="1">
            <span class="switch prestashop-switch fixed-width-lg">
              <input type="radio" name="bo_menu_visible" id="bo_menu_visible_on" value="1"' . ($checked ? ' checked="checked"' : '') . ' onchange="this.form.submit()">
              <label for="bo_menu_visible_on" class="radioCheck">' . $this->l('Oui') . '</label>
              <input type="radio" name="bo_menu_visible" id="bo_menu_visible_off" value="0"' . (!$checked ? ' checked="checked"' : '') . ' onchange="this.form.submit()">
              <label for="bo_menu_visible_off" class="radioCheck">' . $this->l('Non') . '</label>
              <a class="slide-button btn"></a>
            </span>
          </form>
        </div>';
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
     * Playwright).
     */
    private function renderDragDropScript(): string
    {
        $ajaxUrl = self::$currentIndex . '&token=' . $this->token . '&ajax=1&action=UpdatePositions';

        return '
        <script>
        (function(){
          var TAG = "[FL-DnD]";
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
            $db->update($this->table, ['position' => $pos], '`' . bqSQL($this->identifier) . '` = ' . (int) $id);
        }

        header('Content-Type: application/json');
        exit(json_encode(['ok' => true, 'count' => count($ids)]));
    }

    /** Colonne "Hooks" : affiche les hooks ciblés en badges lisibles. */
    public static function renderHooksList($value, $row)
    {
        $hooks = array_filter(array_map('trim', explode(',', (string) $value)));
        if (empty($hooks)) {
            return '<span class="text-muted">—</span>';
        }

        return implode(' ', array_map(static function (string $h): string {
            return '<span class="label label-default">' . htmlspecialchars($h, ENT_QUOTES) . '</span>';
        }, $hooks));
    }

    /** Colonne "Liens" : nombre de liens + lien direct vers leur gestion. */
    public static function renderLinksColumn($value, $row)
    {
        $url = Context::getContext()->link->getAdminLink('AdminFreshapplinksLink')
            . '&id_freshapplinks_list=' . (int) $row['id_freshapplinks_list'];

        return '<a href="' . $url . '" class="btn btn-default btn-xs">'
            . '<i class="icon-link"></i> ' . (int) $value . '</a>';
    }

    public function renderForm()
    {
        $id = (int) Tools::getValue($this->identifier);
        $list = $id ? new FreshapplinksList($id) : null;

        $hooksTable = '
        <div class="form-group">
          <label class="control-label col-lg-3">' . $this->l('Hooks ciblés') . '</label>
          <div class="col-lg-9">';
        foreach (Freshapplinks::AVAILABLE_HOOKS as $hook) {
            $checked = $list && in_array($hook, array_filter(array_map('trim', explode(',', (string) $list->hooks))), true);
            $hooksTable .= '
            <label class="checkbox-inline" style="margin-right:20px">
              <input type="checkbox" name="hooks[]" value="' . $hook . '"' . ($checked ? ' checked' : '') . '> ' . $hook . '
            </label>';
        }
        $hooksTable .= '
            <p class="help-block">' . $this->l('Cette liste sera affichée sur chacun des hooks cochés. Aucune case cochée = liste inactive côté front.') . '</p>
          </div>
        </div>';

        $layout = $list->layout ?? 'column';
        $showTitle = $list ? (bool) $list->show_title : true;
        $layoutField = '
        <div class="form-group">
          <label class="control-label col-lg-3">' . $this->l('Affichage') . '</label>
          <div class="col-lg-9">
            <select name="layout" class="form-control" style="width:auto;display:inline-block">
              <option value="column"' . ('column' === $layout ? ' selected' : '') . '>' . $this->l('En colonne (liens empilés)') . '</option>
              <option value="row"' . ('row' === $layout ? ' selected' : '') . '>' . $this->l('En ligne (liens côte à côte)') . '</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-3">' . $this->l('Afficher le titre') . '</label>
          <div class="col-lg-9">
            <span class="switch prestashop-switch fixed-width-lg">
              <input type="radio" name="show_title" id="show_title_on" value="1"' . ($showTitle ? ' checked' : '') . '>
              <label for="show_title_on">' . $this->l('Oui') . '</label>
              <input type="radio" name="show_title" id="show_title_off" value="0"' . (!$showTitle ? ' checked' : '') . '>
              <label for="show_title_off">' . $this->l('Non') . '</label>
              <a class="slide-button btn"></a>
            </span>
            <p class="help-block">' . $this->l('Si désactivé, le champ "Nom" ci-dessus reste utilisé en interne (BO) mais n\'est pas affiché au-dessus des liens côté front.') . '</p>
          </div>
        </div>';

        $hintLength = $this->l('Valeur CSS avec unité : rem, px, em ou %. Ex : 1rem, 16px.');

        $styleTable = '
        <hr>
        <p class="text-muted"><strong>' . $this->l('Style par défaut des liens de cette liste (vide = hérite du thème, chaque lien peut surcharger individuellement)') . '</strong></p>
        <div class="row">
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $this->l('Couleur icône') . '</label><br>
              <input type="color" class="fpl-color-input" data-pair="icon_color_text" value="' . htmlspecialchars($list->icon_color ?? '' ?: '#000000', ENT_QUOTES) . '" style="width:48px;height:34px">
              <input type="text" name="icon_color" id="fpl-icon-color-hex" value="' . htmlspecialchars($list->icon_color ?? '', ENT_QUOTES) . '" placeholder="vide" class="form-control" style="width:110px;display:inline-block;margin-left:6px" maxlength="7">
            </div>
          </div>
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $this->l('Couleur lien') . '</label><br>
              <input type="color" class="fpl-color-input" data-pair="link_color_text" value="' . htmlspecialchars($list->link_color ?? '' ?: '#000000', ENT_QUOTES) . '" style="width:48px;height:34px">
              <input type="text" name="link_color" id="fpl-link-color-hex" value="' . htmlspecialchars($list->link_color ?? '', ENT_QUOTES) . '" placeholder="vide" class="form-control" style="width:110px;display:inline-block;margin-left:6px" maxlength="7">
            </div>
          </div>
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $this->l('Graisse') . '</label>
              <select name="font_weight" class="form-control">' . $this->fontWeightOptions((string) ($list->font_weight ?? '')) . '</select>
            </div>
          </div>
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $this->l('Transformation') . '</label>
              <select name="text_transform" class="form-control">' . $this->textTransformOptions((string) ($list->text_transform ?? '')) . '</select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $this->l('Famille de police') . '</label>
              <input type="text" name="font_family" class="form-control" placeholder="' . $this->l('vide = police du thème') . '" value="' . htmlspecialchars($list->font_family ?? '', ENT_QUOTES) . '">
              <p class="help-block">' . $this->l('Ex : "Poppins", sans-serif') . '</p>
            </div>
          </div>
          <div class="col-sm-3">
            <div class="form-group">
              <label>' . $this->l('Taille de police') . '</label>
              <input type="text" name="font_size" class="form-control" placeholder="' . $this->l('vide') . '" value="' . htmlspecialchars($list->font_size ?? '', ENT_QUOTES) . '">
              <p class="help-block">' . $hintLength . '</p>
            </div>
          </div>
        </div>
        <script>
        (function(){
          document.querySelectorAll(".fpl-color-input").forEach(function(color){
            var text = document.querySelector(\'[name="\' + color.getAttribute("data-pair").replace("_text", "") + \'"]\');
            if (!text) return;
            color.addEventListener("input", function(){ text.value = color.value; });
            text.addEventListener("input", function(){
              if (/^#[0-9a-fA-F]{3,6}$/.test(text.value)) { color.value = text.value; }
            });
          });
        }());
        </script>';

        $manageLinksBtn = '';
        if ($list && $list->id) {
            $linkUrl = Context::getContext()->link->getAdminLink('AdminFreshapplinksLink') . '&id_freshapplinks_list=' . (int) $list->id;
            $manageLinksBtn = '
        <div class="form-group">
          <label class="control-label col-lg-3"></label>
          <div class="col-lg-9">
            <a href="' . $linkUrl . '" class="btn btn-default"><i class="icon-link"></i> ' . $this->l('Gérer les liens de cette liste') . '</a>
          </div>
        </div>';
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Liste de liens'),
                'icon' => 'icon-link',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Nom'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true,
                    'hint' => $this->l('Nom interne et, selon le thème, titre visible au-dessus de la liste (ex : titre de colonne footer).'),
                ],
                [
                    'type' => 'html',
                    'name' => 'fpl_hooks_html',
                    'html_content' => $hooksTable,
                ],
                [
                    'type' => 'html',
                    'name' => 'fpl_layout_html',
                    'html_content' => $layoutField,
                ],
                [
                    'type' => 'html',
                    'name' => 'fpl_style_html',
                    'html_content' => $styleTable,
                ],
                [
                    'type' => 'html',
                    'name' => 'fpl_manage_links_html',
                    'html_content' => $manageLinksBtn,
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
            ],
            'submit' => [
                'title' => $this->l('Enregistrer'),
            ],
        ];

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitFreshappBoMenuVisibility')) {
            $idTab = (int) Tab::getIdFromClassName('AdminFreshapplinks');
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->active = Tools::getValue('bo_menu_visible') ? 1 : 0;
                $tab->save();
            }
        }

        if (Tools::isSubmit('submitAdd' . $this->table) || Tools::isSubmit('submitUpdate' . $this->table)) {
            $id = (int) Tools::getValue($this->identifier);
            $list = $id ? $this->loadObject() : new FreshapplinksList();

            $this->copyFromPost($list, $this->table);

            $hooks = (array) Tools::getValue('hooks', []);
            $hooks = array_intersect($hooks, Freshapplinks::AVAILABLE_HOOKS);
            $list->hooks = implode(',', $hooks);

            $layout = (string) Tools::getValue('layout', 'column');
            $list->layout = 'row' === $layout ? 'row' : 'column';

            $list->show_title = (bool) Tools::getValue('show_title', 1);

            $list->icon_color = $this->sanitizeHexColor((string) Tools::getValue('icon_color', ''));
            $list->link_color = $this->sanitizeHexColor((string) Tools::getValue('link_color', ''));

            $fw = (string) Tools::getValue('font_weight', '');
            $list->font_weight = in_array($fw, ['100', '200', '300', '400', '500', '600', '700', '800', '900'], true) ? $fw : '';

            $tt = (string) Tools::getValue('text_transform', '');
            $list->text_transform = in_array($tt, ['none', 'uppercase', 'lowercase', 'capitalize'], true) ? $tt : '';

            $list->font_family = trim((string) Tools::getValue('font_family', ''));
            $list->font_size = $this->sanitizeCssLength((string) Tools::getValue('font_size', ''));

            if (empty($this->errors)) {
                $ok = $list->id ? $list->update() : $list->add();
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
}
