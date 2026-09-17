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
 * Ajoute la colonne `layout` (disposition des liens : colonne ou ligne) sur freshapplinks_list.
 */
function upgrade_module_1_0_2(Module $module): bool
{
    try {
        Db::getInstance()->execute(
            'ALTER TABLE `' . _DB_PREFIX_ . 'freshapplinks_list` ADD `layout` VARCHAR(10) NOT NULL DEFAULT \'column\'',
        );
    } catch (Exception $e) {
        // Colonne déjà présente — pas une erreur.
    }

    return true;
}
