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
 * 1.1.2 — entrée de menu déplacée dans Modules et renommée « FS Links ».
 */
function upgrade_module_1_1_2($module): bool
{
    $idTab = (int) Tab::getIdFromClassName('AdminFreshapplinks');
    if ($idTab) {
        $tab = new Tab($idTab);
        $idParent = (int) Tab::getIdFromClassName('AdminParentModulesSf');
        if ($idParent && (int) $tab->id_parent !== $idParent) {
            $tab->id_parent = $idParent;
            $tab->position = Tab::getNewLastPosition($idParent);
        }
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'FS Links';
        }
        if (property_exists($tab, 'wording') && $tab->wording) {
            $tab->wording = 'FS Links';
        }
        $tab->save();
    }

    return true;
}
