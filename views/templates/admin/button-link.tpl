{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license Proprietary - see LICENSE file
 *}
{if $fpl_form_group}<div class="form-group"><label class="control-label col-lg-3"></label><div class="col-lg-9">{else}<p style="margin:10px 0">{/if}
<a href="{$fpl_url|escape:'html':'UTF-8'}" class="btn btn-default"><i class="{$fpl_icon|escape:'html':'UTF-8'}"></i> {$fpl_label|escape:'html':'UTF-8'}</a>
{if $fpl_form_group}</div></div>{else}</p>{/if}
