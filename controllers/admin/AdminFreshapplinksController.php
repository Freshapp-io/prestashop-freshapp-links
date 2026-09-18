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

    /** @return array<string, string> choix de graisse, la valeur vide héritant du thème */
    private function fontWeightOptions(): array
    {
        $options = ['' => '— ' . $this->l('normal (400)') . ' —'];
        foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $w) {
            $options[(string) $w] = (string) $w;
        }

        return $options;
    }

    /** @return array<string, string> */
    private function textTransformOptions(): array
    {
        return ['' => '— ' . $this->l('aucune') . ' —', 'none' => 'None', 'uppercase' => 'UPPERCASE', 'lowercase' => 'lowercase', 'capitalize' => 'Capitalize'];
    }

    /**
     * Module::l() rend un texte déjà échappé pour le HTML, alors que les gabarits échappent à
     * l'affichage : les libellés traduits sont ramenés à du texte brut pour ne pas l'être deux
     * fois. Seules les clés portant des libellés sont concernées, jamais les saisies.
     */
    public static function decodeLabels(array $vars): array
    {
        foreach (['fpl_t', 'fpl_types', 'fpl_weights', 'fpl_transforms', 'fpl_label'] as $key) {
            if (isset($vars[$key])) {
                $vars[$key] = is_array($vars[$key])
                    ? array_map([self::class, 'decode'], $vars[$key])
                    : self::decode($vars[$key]);
            }
        }
        if (isset($vars['fpl_colors'])) {
            foreach ($vars['fpl_colors'] as $i => $color) {
                $vars['fpl_colors'][$i]['label'] = self::decode($color['label']);
            }
        }

        return $vars;
    }

    private static function decode($value): string
    {
        return html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** Rendu d'un gabarit de views/templates/admin. */
    private function fetchTemplate(string $name, array $vars): string
    {
        return self::renderTemplate($name, $vars);
    }

    private static function renderTemplate(string $name, array $vars): string
    {
        $vars = self::decodeLabels($vars);
        $smarty = Context::getContext()->smarty;
        $smarty->assign($vars);

        return $smarty->fetch(_PS_MODULE_DIR_ . 'freshapplinks/views/templates/admin/' . $name . '.tpl');
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

        return $this->fetchTemplate('menu-visibility', [
            'fpl_checked' => $checked,
            'fpl_t' => ['label' => $this->l('Afficher dans le menu du backoffice'), 'yes' => $this->l('Oui'), 'no' => $this->l('Non')],
        ]);
    }

    /**
     * Le glisser-déposer natif de HelperList ne persiste pas l'ordre (le format posté par le
     * core ne correspond pas à ajaxProcessUpdatePositions()). views/js/admin.js reprend la main
     * sur la table désignée ici ; le détail est commenté dans ce fichier.
     */
    private function renderDragDropScript(): string
    {
        return $this->fetchTemplate('dnd', [
            'fpl_ajax_url' => self::$currentIndex . '&token=' . $this->token . '&ajax=1&action=UpdatePositions',
            'fpl_table_id' => 'table-' . $this->table,
            'fpl_tag' => 'FL-DnD',
        ]);
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
        return self::renderTemplate('list-hooks', [
            'fpl_hooks' => array_values(array_filter(array_map('trim', explode(',', (string) $value)))),
        ]);
    }

    /** Colonne "Liens" : nombre de liens + lien direct vers leur gestion. */
    public static function renderLinksColumn($value, $row)
    {
        $url = Context::getContext()->link->getAdminLink('AdminFreshapplinksLink')
            . '&id_freshapplinks_list=' . (int) $row['id_freshapplinks_list'];

        return self::renderTemplate('list-links', ['fpl_url' => $url, 'fpl_count' => (int) $value]);
    }

    public function renderForm()
    {
        $id = (int) Tools::getValue($this->identifier);
        $list = $id ? new FreshapplinksList($id) : null;

        $selectedHooks = $list ? array_filter(array_map('trim', explode(',', (string) $list->hooks))) : [];
        $hooks = [];
        foreach (Freshapplinks::AVAILABLE_HOOKS as $hook) {
            $hooks[] = ['name' => $hook, 'checked' => in_array($hook, $selectedHooks, true)];
        }
        $hooksTable = $this->fetchTemplate('list-form-hooks', [
            'fpl_hooks' => $hooks,
            'fpl_t' => [
                'label' => $this->l('Hooks ciblés'),
                'help' => $this->l('Cette liste sera affichée sur chacun des hooks cochés. Aucune case cochée = liste inactive côté front.'),
            ],
        ]);

        $layoutField = $this->fetchTemplate('list-form-layout', [
            'fpl_layout' => $list->layout ?? 'column',
            'fpl_show_title' => $list ? (bool) $list->show_title : true,
            'fpl_t' => [
                'display' => $this->l('Affichage'),
                'column' => $this->l('En colonne (liens empilés)'),
                'row' => $this->l('En ligne (liens côte à côte)'),
                'show_title' => $this->l('Afficher le titre'),
                'yes' => $this->l('Oui'),
                'no' => $this->l('Non'),
                'show_title_help' => $this->l('Si désactivé, le champ "Nom" ci-dessus reste utilisé en interne (BO) mais n\'est pas affiché au-dessus des liens côté front.'),
            ],
        ]);

        $styleTable = $this->fetchTemplate('list-form-style', [
            'fpl_list' => [
                'icon_color' => (string) ($list->icon_color ?? ''),
                'link_color' => (string) ($list->link_color ?? ''),
                'font_weight' => (string) ($list->font_weight ?? ''),
                'text_transform' => (string) ($list->text_transform ?? ''),
                'font_family' => (string) ($list->font_family ?? ''),
                'font_size' => (string) ($list->font_size ?? ''),
            ],
            'fpl_weights' => $this->fontWeightOptions(),
            'fpl_transforms' => $this->textTransformOptions(),
            'fpl_t' => [
                'intro' => $this->l('Style par défaut des liens de cette liste (vide = hérite du thème, chaque lien peut surcharger individuellement)'),
                'icon_color' => $this->l('Couleur icône'),
                'link_color' => $this->l('Couleur lien'),
                'weight' => $this->l('Graisse'),
                'transform' => $this->l('Transformation'),
                'family' => $this->l('Famille de police'),
                'family_placeholder' => $this->l('vide = police du thème'),
                'family_help' => $this->l('Ex : "Poppins", sans-serif'),
                'size' => $this->l('Taille de police'),
                'size_help' => $this->l('Valeur CSS avec unité : rem, px, em ou %. Ex : 1rem, 16px.'),
                'empty' => $this->l('vide'),
            ],
        ]);

        $manageLinksBtn = '';
        if ($list && $list->id) {
            $manageLinksBtn = $this->fetchTemplate('button-link', [
                'fpl_form_group' => true,
                'fpl_url' => Context::getContext()->link->getAdminLink('AdminFreshapplinksLink') . '&id_freshapplinks_list=' . (int) $list->id,
                'fpl_icon' => 'icon-link',
                'fpl_label' => $this->l('Gérer les liens de cette liste'),
            ]);
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
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/admin.js');
    }
}
