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
 * Enregistre les nouveaux hooks de placement (displayTop, displayNav1/2, displayNavFullWidth,
 * displayBanner, displayAfterBodyOpeningTag, displayBeforeBodyClosingTag) ajoutés à
 * Freshapplinks::AVAILABLE_HOOKS. registerHook() est idempotent : les hooks déjà enregistrés
 * (displayFooter, displayNav) ne sont pas dupliqués.
 */
function upgrade_module_1_0_1(Module $module): bool
{
    $ok = true;
    foreach (Freshapplinks::AVAILABLE_HOOKS as $hook) {
        $ok = $module->registerHook($hook) && $ok;
    }

    return $ok;
}
