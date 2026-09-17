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

/**
 * Une liste de liens, affichable sur un ou plusieurs hooks. Porte les valeurs de style
 * par défaut (icône/lien/police) héritées par ses liens sauf surcharge par lien.
 */
class FreshapplinksList extends ObjectModel
{
    /** @var int */
    public $id_freshapplinks_list;

    /** @var string liste de hooks séparés par virgule, ex: "displayFooter,displayNav" */
    public $hooks = '';

    /** @var int */
    public $position = 0;

    /** @var bool */
    public $active = 1;

    /** @var string couleur hexa de l'icône par défaut ('' = currentColor) */
    public $icon_color = '';

    /** @var string couleur hexa du texte du lien par défaut */
    public $link_color = '';

    /** @var string graisse par défaut (100-900) */
    public $font_weight = '';

    /** @var string famille de police CSS par défaut */
    public $font_family = '';

    /** @var string text-transform par défaut */
    public $text_transform = '';

    /** @var string taille de police par défaut (ex: 1rem) */
    public $font_size = '';

    /** @var string disposition des liens : 'column' (empilés, un par ligne) ou 'row' (en ligne, côte à côte) */
    public $layout = 'column';

    /** @var bool affiche le nom de la liste comme titre visible au-dessus des liens */
    public $show_title = 1;

    /** @var string nom de la liste (multilangue — sert aussi de titre visible, ex. titre de colonne footer, si show_title est activé) */
    public $name;

    public static $definition = [
        'table' => 'freshapplinks_list',
        'primary' => 'id_freshapplinks_list',
        'multilang' => true,
        'fields' => [
            'hooks' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'active' => ['type' => self::TYPE_BOOL],
            'icon_color' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'link_color' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'font_weight' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'font_family' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'text_transform' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'font_size' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'layout' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'show_title' => ['type' => self::TYPE_BOOL],

            // Lang
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true],
        ],
    ];

    /**
     * Réordonnancement par drag & drop (plat, sans groupement — reprend le pattern utilisé par
     * les entités core PS comme CMS::updatePosition()).
     */
    public function updatePosition($way, $position)
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'freshapplinks_list';

        if (!$res = $db->executeS('SELECT `id_freshapplinks_list`, `position` FROM `' . $table . '` ORDER BY `position` ASC')) {
            return false;
        }

        $movedItem = null;
        foreach ($res as $row) {
            if ((int) $row['id_freshapplinks_list'] === (int) $this->id) {
                $movedItem = $row;
            }
        }
        if (!$movedItem || !isset($position)) {
            return false;
        }

        return $db->execute(
            'UPDATE `' . $table . '` SET `position` = `position` ' . ($way ? '- 1' : '+ 1') . '
             WHERE `position` ' . ($way
                ? '> ' . (int) $movedItem['position'] . ' AND `position` <= ' . (int) $position
                : '< ' . (int) $movedItem['position'] . ' AND `position` >= ' . (int) $position),
        ) && $db->execute(
            'UPDATE `' . $table . '` SET `position` = ' . (int) $position . '
             WHERE `id_freshapplinks_list` = ' . (int) $movedItem['id_freshapplinks_list'],
        );
    }
}
