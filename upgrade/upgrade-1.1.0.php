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
 * 1.1.0 — le module devient FreshApp Links, compatible PrestaShop 1.7 à 9, et embarque ses
 * propres icônes au lieu d'utiliser le sprite du thème.
 *
 * L'onglet d'administration portait l'ancien nom « FS Links » dans toutes les langues : il
 * est renommé, sans toucher aux listes ni aux liens configurés.
 */
function upgrade_module_1_1_0($module): bool
{
    $idTab = (int) Db::getInstance()->getValue(
        'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "' . pSQL(Freshapplinks::TAB_LIST_CLASS) . '"',
    );
    if ($idTab) {
        Db::getInstance()->update('tab_lang', ['name' => pSQL('FreshApp Links')], '`id_tab` = ' . $idTab);
    }

    return true;
}
