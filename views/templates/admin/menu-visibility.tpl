{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license Proprietary - see LICENSE file
 *}
<div class="panel" style="display:flex;align-items:center;justify-content:flex-end;gap:12px;padding:12px 20px;">
  <span>{$fpl_t.label|escape:'html':'UTF-8'}</span>
  <form method="post" style="margin:0">
    <input type="hidden" name="submitFreshappBoMenuVisibility" value="1">
    <span class="switch prestashop-switch fixed-width-lg">
      <input type="radio" name="bo_menu_visible" id="bo_menu_visible_on" value="1"{if $fpl_checked} checked="checked"{/if}{if !empty($fa_menu_demo)} disabled{/if} onchange="this.form.submit()">
      <label for="bo_menu_visible_on" class="radioCheck">{$fpl_t.yes|escape:'html':'UTF-8'}</label>
      <input type="radio" name="bo_menu_visible" id="bo_menu_visible_off" value="0"{if !$fpl_checked} checked="checked"{/if}{if !empty($fa_menu_demo)} disabled{/if} onchange="this.form.submit()">
      <label for="bo_menu_visible_off" class="radioCheck">{$fpl_t.no|escape:'html':'UTF-8'}</label>
      <a class="slide-button btn"></a>
    </span>
    {if !empty($fa_menu_demo)}<small class="text-muted">{l s='Verrouillé en mode démonstration' mod='freshapplinks'}</small>{/if}
  </form>
</div>
