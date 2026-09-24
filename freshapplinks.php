<?php
/**
 * FreshApp Links.
 *
 * Listes de liens configurables (produit / catégorie / page CMS / URL libre) affichables
 * sur un ou plusieurs hooks, avec icône et style (couleur, police) par liste et par lien.
 *
 * @author    FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license   Proprietary - see LICENSE file
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/FreshapplinksList.php';
require_once __DIR__ . '/classes/FreshapplinksLink.php';

class Freshapplinks extends Module
{
    public const TAB_LIST_CLASS = 'AdminFreshapplinks';
    public const TAB_LINK_CLASS = 'AdminFreshapplinksLink';

    /**
     * Hooks qu'une liste peut cibler (choix multiple en BO). Même éventail de hooks de
     * placement que freshappprestamegamenu, pour pouvoir positionner une liste de liens
     * n'importe où dans le thème. `displayHeader` est volontairement exclu : ce hook est
     * appelé dans le <head> de la page pour l'enregistrement d'assets CSS/JS (voir
     * hookDisplayHeader ci-dessous) — y retourner du HTML de contenu y serait invalide.
     */
    public const AVAILABLE_HOOKS = [
        'displayTop',
        'displayNav',
        'displayNav1',
        'displayNav2',
        'displayNavFullWidth',
        'displayBanner',
        'displayFooter',
        'displayFooterLegal',
        'displayAfterBodyOpeningTag',
        'displayBeforeBodyClosingTag',
    ];

    /** Boutique de démonstration : constante _FA_DEMO_MODE_ définie par l'instance. */
    public static function isDemoMode(): bool
    {
        return defined('_FA_DEMO_MODE_') && (bool) constant('_FA_DEMO_MODE_');
    }

    public function __construct()
    {
        $this->name = 'freshapplinks';
        $this->tab = 'front_office_features';
        $this->version = '1.1.4';
        $this->author = 'FreshApp.io';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => '9.99.99'];

        parent::__construct();

        $this->displayName = $this->l('FreshApp Links');
        $this->description = $this->l('Configurable link lists (product, category, CMS page or custom URL) displayed on any hook, with custom icon and style.');
    }

    public function install()
    {
        if (!parent::install() || !$this->installSql() || !$this->installTabs() || !$this->registerHook('displayHeader')) {
            return false;
        }
        foreach (self::AVAILABLE_HOOKS as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }

    public function uninstall()
    {
        $ok = $this->uninstallSql();
        $ok = $this->uninstallTabs() && $ok;

        return parent::uninstall() && $ok;
    }

    public function reset()
    {
        if (!$this->uninstallSql()) {
            return false;
        }
        if (!$this->installSql()) {
            return false;
        }

        return parent::reset();
    }

    /* ------------------------------------------------------------------ */
    /* Tabs */
    /* ------------------------------------------------------------------ */

    protected function installTabs(): bool
    {
        $idList = $this->addTab(self::TAB_LIST_CLASS, 'FS Links', (int) Tab::getIdFromClassName('AdminParentModulesSf'));
        if (!$idList) {
            return false;
        }
        $idLink = $this->addTab(self::TAB_LINK_CLASS, 'Liens', $idList);

        return (bool) $idLink;
    }

    protected function uninstallTabs(): bool
    {
        $ok = true;
        foreach ([self::TAB_LINK_CLASS, self::TAB_LIST_CLASS] as $className) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab) {
                $ok = (new Tab($idTab))->delete() && $ok;
            }
        }

        return $ok;
    }

    private function addTab(string $className, string $name, int $idParent): int
    {
        $tab = new Tab();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }
        $tab->class_name = $className;
        $tab->id_parent = $idParent;
        $tab->module = $this->name;
        // Entrée de menu masquée par défaut (opt-in via une case à cocher sur l'écran du module
        // lui-même) — l'onglet existe toujours et reste pleinement accessible via "Configurer",
        // juste absent de l'arbre de navigation. Voir AdminFreshapplinksController pour la case
        // qui bascule ça (seulement sur l'onglet liste parent — le masquer masque aussi l'enfant
        // "Liens" de l'arbre de navigation).
        $tab->active = 0;

        return $tab->add() ? (int) $tab->id : 0;
    }

    /* ------------------------------------------------------------------ */
    /* SQL */
    /* ------------------------------------------------------------------ */

    protected function installSql(): bool
    {
        $sql = file_get_contents(__DIR__ . '/install/install.sql');
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);

        $queries = preg_split('/;\s*[\r\n]+/', trim($sql));
        foreach ($queries as $query) {
            $query = trim($query);
            if ('' === $query) {
                continue;
            }
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    protected function uninstallSql(): bool
    {
        $db = Db::getInstance();
        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'freshapplinks_link_lang`');
        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'freshapplinks_link`');
        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'freshapplinks_list_lang`');
        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'freshapplinks_list`');

        return true;
    }

    /* ------------------------------------------------------------------ */
    /* Hooks front */
    /* ------------------------------------------------------------------ */

    public function hookDisplayTop($params)
    {
        return $this->renderForHook('displayTop');
    }

    public function hookDisplayNav($params)
    {
        return $this->renderForHook('displayNav');
    }

    public function hookDisplayNav1($params)
    {
        return $this->renderForHook('displayNav1');
    }

    public function hookDisplayNav2($params)
    {
        return $this->renderForHook('displayNav2');
    }

    public function hookDisplayNavFullWidth($params)
    {
        return $this->renderForHook('displayNavFullWidth');
    }

    public function hookDisplayBanner($params)
    {
        return $this->renderForHook('displayBanner');
    }

    public function hookDisplayFooter($params)
    {
        return $this->renderForHook('displayFooter');
    }

    public function hookDisplayFooterLegal($params)
    {
        return $this->renderForHook('displayFooterLegal');
    }

    public function hookDisplayAfterBodyOpeningTag($params)
    {
        return $this->renderForHook('displayAfterBodyOpeningTag');
    }

    public function hookDisplayBeforeBodyClosingTag($params)
    {
        return $this->renderForHook('displayBeforeBodyClosingTag');
    }

    public function hookDisplayHeader($params)
    {
        $this->context->controller->registerStylesheet(
            'module-freshapplinks-front',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 150, 'server' => 'local'],
        );
    }

    /**
     * Rend toutes les listes actives ciblant le hook donné (FIND_IN_SET sur la colonne `hooks`).
     */
    /**
     * URL du sprite d'icônes embarqué : le module ne dépend d'aucun sprite fourni par le thème.
     * Le numéro de version force le navigateur à recharger le fichier après une mise à jour.
     */
    public function getIconsUrl(): string
    {
        return $this->getPathUri() . 'views/img/icons/icons.svg?v=' . $this->version;
    }

    /**
     * Identifiants disponibles dans le sprite, proposés en suggestion dans le back-office.
     *
     * @return string[]
     */
    public function getIconNames(): array
    {
        $file = $this->getLocalPath() . 'views/img/icons/icons.svg';
        if (!is_file($file) || !preg_match_all('/<symbol id="([a-z0-9-]+)"/', (string) file_get_contents($file), $m)) {
            return [];
        }

        return $m[1];
    }

    private function renderForHook(string $hookName): string
    {
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        $lists = Db::getInstance()->executeS(
            'SELECT a.*, b.name FROM `' . _DB_PREFIX_ . 'freshapplinks_list` a
             LEFT JOIN `' . _DB_PREFIX_ . 'freshapplinks_list_lang` b
                ON b.id_freshapplinks_list = a.id_freshapplinks_list AND b.id_lang = ' . $idLang . '
             WHERE a.active = 1 AND FIND_IN_SET(\'' . pSQL($hookName) . '\', a.hooks)
             ORDER BY a.position ASC',
        );

        if (empty($lists)) {
            return '';
        }

        $html = '';
        foreach ($lists as $list) {
            $html .= $this->renderList((array) $list, $idLang, $idShop, $hookName);
        }

        return $html;
    }

    /**
     * Résout les liens actifs d'une liste (cible + icône + style fusionné) et rend le template.
     */
    private function renderList(array $list, int $idLang, int $idShop, string $hookName): string
    {
        $rows = Db::getInstance()->executeS(
            'SELECT a.*, b.label, b.custom_url FROM `' . _DB_PREFIX_ . 'freshapplinks_link` a
             LEFT JOIN `' . _DB_PREFIX_ . 'freshapplinks_link_lang` b
                ON b.id_freshapplinks_link = a.id_freshapplinks_link AND b.id_lang = ' . $idLang . '
             WHERE a.id_freshapplinks_list = ' . (int) $list['id_freshapplinks_list'] . ' AND a.active = 1
             ORDER BY a.position ASC',
        );

        $listStyle = [
            'icon_color' => (string) $list['icon_color'],
            'link_color' => (string) $list['link_color'],
            'font_weight' => (string) $list['font_weight'],
            'font_family' => (string) $list['font_family'],
            'text_transform' => (string) $list['text_transform'],
            'font_size' => (string) $list['font_size'],
        ];

        $links = [];
        foreach ($rows as $row) {
            $href = $this->resolveLinkHref((array) $row, $idLang, $idShop);
            if (null === $href) {
                // Cible supprimée depuis (produit/catégorie/page CMS introuvable) : on ignore
                // silencieusement ce lien plutôt que d'afficher un lien mort.
                continue;
            }
            $links[] = array_merge((array) $row, [
                '_href' => $href,
                '_style' => $this->buildLinkStyle((array) $row, $listStyle),
            ]);
        }

        if (empty($links)) {
            return '';
        }

        $this->context->smarty->assign([
            'freshapplinks_list' => $list,
            'freshapplinks_links' => $links,
            'freshapplinks_hook' => $hookName,
            'module_dir' => $this->_path,
            'freshapplinks_icons' => $this->getIconsUrl(),
        ]);

        return $this->fetch('module:freshapplinks/views/templates/hook/front.tpl');
    }

    /**
     * Construit l'URL réelle d'un lien selon sa cible. Retourne null si la cible est introuvable
     * (produit/catégorie/CMS supprimé depuis) — le lien est alors simplement omis du rendu.
     */
    private function resolveLinkHref(array $row, int $idLang, int $idShop): ?string
    {
        $type = (string) $row['target_type'];
        $targetId = (int) ($row['target_id'] ?? 0);
        $link = $this->context->link;

        switch ($type) {
            case 'product':
                if ($targetId <= 0 || !Db::getInstance()->getValue(
                    'SELECT id_product FROM `' . _DB_PREFIX_ . 'product_shop`
                     WHERE id_product = ' . $targetId . ' AND id_shop = ' . $idShop,
                )) {
                    return null;
                }

                return $link->getProductLink($targetId);

            case 'category':
                if ($targetId <= 0 || !Db::getInstance()->getValue(
                    'SELECT id_category FROM `' . _DB_PREFIX_ . 'category` WHERE id_category = ' . $targetId,
                )) {
                    return null;
                }

                return $link->getCategoryLink($targetId);

            case 'cms':
                if ($targetId <= 0 || !Db::getInstance()->getValue(
                    'SELECT id_cms FROM `' . _DB_PREFIX_ . 'cms` WHERE id_cms = ' . $targetId . ' AND active = 1',
                )) {
                    return null;
                }

                return $link->getCMSLink($targetId);

            case 'custom':
                $url = trim((string) ($row['custom_url'] ?? ''));

                return '' !== $url ? $url : null;

            default:
                return null;
        }
    }

    /**
     * Fusionne les surcharges JSON du lien avec le style par défaut de la liste et construit la
     * chaîne de custom properties CSS à injecter en inline sur l'élément du lien.
     */
    private function buildLinkStyle(array $row, array $listStyle): string
    {
        $ov = [];
        if (!empty($row['style_overrides'])) {
            $decoded = json_decode((string) $row['style_overrides'], true);
            if (is_array($decoded)) {
                $ov = $decoded;
            }
        }

        $effective = array_merge($listStyle, array_intersect_key($ov, $listStyle));

        $parts = [];
        if ('' !== $effective['icon_color']) {
            $parts[] = '--fpl-icon-color:' . $effective['icon_color'];
        }
        if ('' !== $effective['link_color']) {
            $parts[] = '--fpl-link-color:' . $effective['link_color'];
        }
        if ('' !== $effective['font_weight']) {
            $parts[] = '--fpl-font-weight:' . $effective['font_weight'];
        }
        if ('' !== $effective['font_family']) {
            $parts[] = '--fpl-font-family:' . $effective['font_family'];
        }
        if ('' !== $effective['text_transform']) {
            $parts[] = '--fpl-text-transform:' . $effective['text_transform'];
        }
        if ('' !== $effective['font_size']) {
            $parts[] = '--fpl-font-size:' . $effective['font_size'];
        }

        return empty($parts) ? '' : implode(';', $parts) . ';';
    }

    /**
     * Redirige "Configurer" vers le contrôleur BO des listes.
     */
    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminFreshapplinks'),
        );
    }
}
