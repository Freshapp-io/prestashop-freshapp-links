{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license GPL-3.0-or-later
 *}
<div class="form-group">
  <label class="control-label col-lg-3">{$fpl_t.display|escape:'html':'UTF-8'}</label>
  <div class="col-lg-9">
    <select name="layout" class="form-control" style="width:auto;display:inline-block">
      <option value="column"{if $fpl_layout == 'column'} selected{/if}>{$fpl_t.column|escape:'html':'UTF-8'}</option>
      <option value="row"{if $fpl_layout == 'row'} selected{/if}>{$fpl_t.row|escape:'html':'UTF-8'}</option>
    </select>
  </div>
</div>
<div class="form-group">
  <label class="control-label col-lg-3">{$fpl_t.show_title|escape:'html':'UTF-8'}</label>
  <div class="col-lg-9">
    <span class="switch prestashop-switch fixed-width-lg">
      <input type="radio" name="show_title" id="show_title_on" value="1"{if $fpl_show_title} checked{/if}>
      <label for="show_title_on">{$fpl_t.yes|escape:'html':'UTF-8'}</label>
      <input type="radio" name="show_title" id="show_title_off" value="0"{if !$fpl_show_title} checked{/if}>
      <label for="show_title_off">{$fpl_t.no|escape:'html':'UTF-8'}</label>
      <a class="slide-button btn"></a>
    </span>
    <p class="help-block">{$fpl_t.show_title_help|escape:'html':'UTF-8'}</p>
  </div>
</div>
