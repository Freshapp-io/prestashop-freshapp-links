{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license GPL-3.0-or-later
 *}
{if $fpl_hooks}{foreach $fpl_hooks as $fpl_hook}<span class="label label-default">{$fpl_hook|escape:'html':'UTF-8'}</span> {/foreach}{else}<span class="text-muted">—</span>{/if}
