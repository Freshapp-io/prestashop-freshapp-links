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
 * 1.1.4 — plus de lecture du contexte global dans les contrôleurs, échappement explicite dans
 * le gabarit d'affichage, types corrigés, code inutilisé retiré, en-tête de licence du script
 * d'administration : points relevés par le validateur Addons. Rien à migrer.
 */
function upgrade_module_1_1_4($module): bool
{
    return true;
}
