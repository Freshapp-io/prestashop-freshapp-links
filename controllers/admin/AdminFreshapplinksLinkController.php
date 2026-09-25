<?php
/**
 * FreshApp Links.
 *
 * @author    FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license   GPL-3.0-or-later
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
        $this->bootstrap = true;

        $this->table = 'freshapplinks_link';
        $this->className = 'FreshapplinksLink';
        $this->identifier = 'id_freshapplinks_link';
        $this->lang = true;
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';

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
        $module = $this->module;

        return $module instanceof Freshapplinks ? $module->l($string) : $string;
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

    private function sanitizeCssLength(string $raw): string
    {
        $raw = trim($raw);

        return preg_match('/^\d*\.?\d+(rem|px|em|%)$/', $raw) ? $raw : '';
    }

    /** Rendu d'un gabarit de views/templates/admin. */
    private function fetchTemplate(string $name, array $vars): string
    {
        require_once __DIR__ . '/AdminFreshapplinksController.php';
        $this->context->smarty->assign(AdminFreshapplinksController::decodeLabels($vars));

        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/' . $name . '.tpl');
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
        $back = $this->fetchTemplate('button-link', [
            'fpl_form_group' => false,
            'fpl_url' => $backUrl,
            'fpl_icon' => 'icon-arrow-left',
            'fpl_label' => $this->l('Retour aux listes'),
        ]);

        return $back . parent::renderList() . $this->renderDragDropScript();
    }

    /**
     * Glisser-déposer des liens, limité à la liste courante par ajaxProcessUpdatePositions() :
     * voir views/js/admin.js.
     */
    private function renderDragDropScript(): string
    {
        return $this->fetchTemplate('dnd', [
            'fpl_ajax_url' => self::$currentIndex . '&token=' . $this->token . '&ajax=1&action=UpdatePositions',
            'fpl_table_id' => 'table-' . $this->table,
            'fpl_tag' => 'FLL-DnD',
        ]);
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

        $customUrls = [];
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
            $customUrls[] = ['id_lang' => $langId, 'iso' => (string) $language['iso_code'], 'value' => $val];
        }

        $targetTypeField = $this->fetchTemplate('link-form-target', [
            'fpl_target_type' => $targetType,
            'fpl_types' => ['custom' => $this->l('URL libre'), 'product' => $this->l('Produit'), 'category' => $this->l('Catégorie'), 'cms' => $this->l('Page CMS')],
            'fpl_t' => ['type' => $this->l('Type de cible')],
        ]);

        $targetSearchBlock = $this->fetchTemplate('link-form-search', [
            'fpl_target_type' => $targetType,
            'fpl_target_id' => $targetId,
            'fpl_target_name' => $targetName,
            'fpl_ajax_url' => $ajaxUrl,
            'fpl_urls' => $customUrls,
            'fpl_t' => [
                'search' => $this->l('Rechercher la cible'),
                'search_placeholder' => $this->l('Tapez pour rechercher...'),
                'search_help' => $this->l('Sélectionnez un élément dans la liste proposée pendant la saisie.'),
                'urls' => $this->l('URL (par langue)'),
            ],
        ]);

        $inherit = '— ' . $this->l('hérite de la liste') . ' —';
        $weights = ['' => $inherit];
        foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $w) {
            $weights[(string) $w] = (string) $w;
        }
        $iconImage = $ovGet($ov, 'icon_image') ?: (string) ($link->icon_image ?? '');

        $iconBlock = $this->fetchTemplate('link-form-icon', [
            'fpl_icon_class' => (string) ($link->icon_class ?? ''),
            'fpl_icon_names' => $this->module instanceof Freshapplinks ? $this->module->getIconNames() : [],
            'fpl_icon_image_url' => $iconImage ? $this->module->getPathUri() . $iconImage : '',
            'fpl_colors' => [
                ['name' => 'icon_color_text', 'label' => $this->l('Couleur icône'), 'value' => $ovGet($ov, 'icon_color')],
                ['name' => 'link_color_text', 'label' => $this->l('Couleur lien'), 'value' => $ovGet($ov, 'link_color')],
            ],
            'fpl_weights' => $weights,
            'fpl_transforms' => ['' => $inherit, 'none' => 'None', 'uppercase' => 'UPPERCASE', 'lowercase' => 'lowercase', 'capitalize' => 'Capitalize'],
            'fpl_ov' => [
                'font_weight' => $ovGet($ov, 'font_weight'),
                'text_transform' => $ovGet($ov, 'text_transform'),
                'font_family' => $ovGet($ov, 'font_family'),
                'font_size' => $ovGet($ov, 'font_size'),
            ],
            'fpl_t' => [
                'icon' => $this->l('Icône'),
                'icon_name' => $this->l('Icône — nom dans le jeu embarqué'),
                'icon_name_help' => $this->l('Icônes fournies par le module, indépendantes du thème : commencez à taper pour voir la liste.'),
                'icon_image' => $this->l('Icône — image'),
                'icon_image_help' => $this->l('Formats acceptés : SVG, PNG, JPG, WEBP. Une image prime sur une icône du sprite si les deux sont renseignées.'),
                'style_intro' => $this->l('Style de ce lien (vide = hérite du style par défaut de la liste)'),
                'hex_help' => $this->l('Format hexadécimal #RRGGBB ou #RGB. Vide = hérite du style de la liste.'),
                'weight' => $this->l('Graisse'),
                'transform' => $this->l('Transformation'),
                'family' => $this->l('Famille de police'),
                'size' => $this->l('Taille de police'),
                'size_help' => $this->l('Valeur CSS avec unité : rem, px, em ou %. Ex : 1rem, 16px.'),
                'inherit' => $this->l('vide = liste'),
            ],
        ]);

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
                    'html_content' => $targetTypeField,
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
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/admin.js');
    }
}
