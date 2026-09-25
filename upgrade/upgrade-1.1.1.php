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
 * 1.1.1 — conformité PrestaShop Addons (balisage déplacé dans des gabarits).
 *
 * Rien n'est à migrer en base. Ce fichier existe pour que PrestaShop reconnaisse
 * la montée de version et enregistre le nouveau numéro au lieu de garder l'ancien.
 */
function upgrade_module_1_1_1($module): bool
{
    return true;
}
