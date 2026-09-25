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
 * 1.1.5 — la méthode reset() du module, qui appelait une méthode parente inexistante, est
 * retirée : PrestaShop réinitialise un module en le désinstallant puis en le réinstallant.
 * Onglet créé avec un booléen. Rien à migrer.
 */
function upgrade_module_1_1_5($module): bool
{
    return true;
}
