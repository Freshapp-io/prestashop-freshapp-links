{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license Proprietary - see LICENSE file
 *}
<div class="form-group">
  <label class="control-label col-lg-3">{$fpl_t.label|escape:'html':'UTF-8'}</label>
  <div class="col-lg-9">
    {foreach $fpl_hooks as $fpl_hook}
      <label class="checkbox-inline" style="margin-right:20px">
        <input type="checkbox" name="hooks[]" value="{$fpl_hook.name|escape:'html':'UTF-8'}"{if $fpl_hook.checked} checked{/if}> {$fpl_hook.name|escape:'html':'UTF-8'}
      </label>
    {/foreach}
    <p class="help-block">{$fpl_t.help|escape:'html':'UTF-8'}</p>
  </div>
</div>
