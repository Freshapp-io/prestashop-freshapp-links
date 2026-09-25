{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license GPL-3.0-or-later
 *}
<div class="form-group" id="fpl-target-search-wrap"{if $fpl_target_type == 'custom'} style="display:none"{/if}>
  <label class="control-label col-lg-3">{$fpl_t.search|escape:'html':'UTF-8'}</label>
  <div class="col-lg-9" style="position:relative">
    <input type="text" id="fpl-target-search" class="form-control" autocomplete="off" data-ajax-url="{$fpl_ajax_url|escape:'html':'UTF-8'}"
           placeholder="{$fpl_t.search_placeholder|escape:'html':'UTF-8'}" value="{$fpl_target_name|escape:'html':'UTF-8'}">
    <input type="hidden" name="target_id" id="fpl-target-id" value="{$fpl_target_id|intval}">
    <div id="fpl-target-results" style="position:absolute;z-index:100;background:#fff;border:1px solid #ddd;width:100%;max-height:220px;overflow:auto;display:none"></div>
    <p class="help-block">{$fpl_t.search_help|escape:'html':'UTF-8'}</p>
  </div>
</div>
<div class="form-group" id="fpl-target-custom-wrap"{if $fpl_target_type != 'custom'} style="display:none"{/if}>
  <label class="control-label col-lg-3">{$fpl_t.urls|escape:'html':'UTF-8'}</label>
  <div class="col-lg-9">
    {foreach $fpl_urls as $fpl_url}
      <div class="fpl-custom-url-field" style="margin-bottom:6px">
        <span class="input-group-addon" style="display:inline-block;width:40px">{$fpl_url.iso|upper|escape:'html':'UTF-8'}</span>
        <input type="text" name="custom_url_{$fpl_url.id_lang|intval}" class="form-control" style="display:inline-block;width:calc(100% - 50px)" placeholder="https://..." value="{$fpl_url.value|escape:'html':'UTF-8'}">
      </div>
    {/foreach}
  </div>
</div>
