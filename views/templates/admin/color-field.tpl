{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license GPL-3.0-or-later
 *}
{* Sélecteur de couleur couplé à un champ texte hexadécimal (synchronisés par views/js/admin.js). *}
<input type="color" class="fpl-color-input" data-target="{$fpl_name|escape:'html':'UTF-8'}" value="{if $fpl_value}{$fpl_value|escape:'html':'UTF-8'}{else}#000000{/if}" style="width:48px;height:34px;padding:2px 4px;cursor:pointer">
<input type="text" name="{$fpl_name|escape:'html':'UTF-8'}" class="form-control" value="{$fpl_value|escape:'html':'UTF-8'}" placeholder="{$fpl_placeholder|escape:'html':'UTF-8'}" style="width:110px;display:inline-block;margin-left:6px" maxlength="7">
