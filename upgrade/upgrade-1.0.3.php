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

/**
 * Ajoute la colonne `show_title` (afficher ou non le nom de la liste comme titre visible)
 * sur freshapplinks_list.
 */
function upgrade_module_1_0_3(Module $module): bool
{
    try {
        Db::getInstance()->execute(
            'ALTER TABLE `' . _DB_PREFIX_ . 'freshapplinks_list` ADD `show_title` TINYINT(1) NOT NULL DEFAULT 1',
        );
    } catch (Exception $e) {
        // Colonne déjà présente — pas une erreur.
    }

    return true;
}
