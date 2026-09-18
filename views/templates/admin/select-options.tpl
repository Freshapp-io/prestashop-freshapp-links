{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license Proprietary - see LICENSE file
 *}
{foreach $fpl_options as $fpl_value => $fpl_label}<option value="{$fpl_value|escape:'html':'UTF-8'}"{if $fpl_value == $fpl_current} selected{/if}>{$fpl_label|escape:'html':'UTF-8'}</option>{/foreach}
