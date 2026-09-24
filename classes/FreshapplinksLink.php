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
 * Un lien au sein d'une FreshapplinksList : cible (produit/catégorie/page CMS/URL libre),
 * icône (class sprite ou image, comme le megamenu) et surcharges de style par lien
 * (style_overrides JSON, même principe que freshappprestahero).
 */
class FreshapplinksLink extends ObjectModel
{
    /** @var int */
    public $id_freshapplinks_link;

    /** @var int */
    public $id_freshapplinks_list;

    /** @var int */
    public $position = 0;

    /** @var bool */
    public $active = true;

    /** @var string product|category|cms|custom */
    public $target_type = 'custom';

    /** @var int|null id_product / id_category / id_cms selon target_type */
    public $target_id;

    /** @var string _self|_blank */
    public $link_target = '_self';

    /** @var string nom d'icône du sprite SVG du thème */
    public $icon_class = '';

    /** @var string chemin (relatif au module) de l'image d'icône uploadée */
    public $icon_image = '';

    /** @var string JSON — surcharges de style (icon_color, link_color, font_weight, font_family, text_transform, font_size) */
    public $style_overrides = '';

    // Champs multilangues
    /** @var string texte du lien affiché */
    public $label;

    /** @var string URL libre (uniquement si target_type = custom) */
    public $custom_url;

    public static $definition = [
        'table' => 'freshapplinks_link',
        'primary' => 'id_freshapplinks_link',
        'multilang' => true,
        'fields' => [
            'id_freshapplinks_list' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'active' => ['type' => self::TYPE_BOOL],
            'target_type' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'target_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
            'link_target' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'icon_class' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'icon_image' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'style_overrides' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],

            // Lang
            'label' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true],
            'custom_url' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => false],
        ],
    ];

    /**
     * Réordonnancement par drag & drop, scopé aux liens frères au sein de la même liste (reprend
     * le pattern utilisé par les entités core PS comme CMS::updatePosition(), groupé par
     * id_cms_category).
     */
    public function updatePosition($way, $position)
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'freshapplinks_link';

        if (!$res = $db->executeS(
            'SELECT `id_freshapplinks_link`, `position`, `id_freshapplinks_list` FROM `' . $table . '`
             WHERE `id_freshapplinks_list` = ' . (int) $this->id_freshapplinks_list . '
             ORDER BY `position` ASC',
        )) {
            return false;
        }

        $movedItem = null;
        foreach ($res as $row) {
            if ((int) $row['id_freshapplinks_link'] === (int) $this->id) {
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
                : '< ' . (int) $movedItem['position'] . ' AND `position` >= ' . (int) $position) . '
             AND `id_freshapplinks_list` = ' . (int) $movedItem['id_freshapplinks_list'],
        ) && $db->execute(
            'UPDATE `' . $table . '` SET `position` = ' . (int) $position . '
             WHERE `id_freshapplinks_link` = ' . (int) $movedItem['id_freshapplinks_link'] . '
             AND `id_freshapplinks_list` = ' . (int) $movedItem['id_freshapplinks_list'],
        );
    }
}
