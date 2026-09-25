{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license GPL-3.0-or-later
 *}
<hr>
<p class="text-muted"><strong>{$fpl_t.icon|escape:'html':'UTF-8'}</strong></p>
<div class="row">
  <div class="col-sm-4">
    <div class="form-group">
      <label>{$fpl_t.icon_name|escape:'html':'UTF-8'}</label>
      <input type="text" name="icon_class" class="form-control" list="fa-module-icons" placeholder="icon-arrow-right" value="{$fpl_icon_class|escape:'html':'UTF-8'}">
      <datalist id="fa-module-icons">{foreach $fpl_icon_names as $fpl_icon_name}<option value="{$fpl_icon_name|escape:'html':'UTF-8'}"></option>{/foreach}</datalist>
      <p class="help-block">{$fpl_t.icon_name_help|escape:'html':'UTF-8'}</p>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="form-group">
      <label>{$fpl_t.icon_image|escape:'html':'UTF-8'}</label>
      {if $fpl_icon_image_url}<p><img src="{$fpl_icon_image_url|escape:'html':'UTF-8'}" alt="" style="max-height:24px"></p>{/if}
      <input type="file" name="icon_image" accept=".svg,.png,.jpg,.jpeg,.webp">
      <p class="help-block">{$fpl_t.icon_image_help|escape:'html':'UTF-8'}</p>
    </div>
  </div>
</div>
<p class="text-muted" style="margin-top:15px"><strong>{$fpl_t.style_intro|escape:'html':'UTF-8'}</strong></p>
<div class="row">
  {foreach $fpl_colors as $fpl_color}
    <div class="col-sm-3">
      <div class="form-group">
        <label>{$fpl_color.label|escape:'html':'UTF-8'}</label>
        <div style="display:flex;align-items:center;gap:8px">
          {include file='./color-field.tpl' fpl_name=$fpl_color.name fpl_value=$fpl_color.value fpl_placeholder=$fpl_t.inherit}
        </div>
        <p class="help-block">{$fpl_t.hex_help|escape:'html':'UTF-8'}</p>
      </div>
    </div>
  {/foreach}
  <div class="col-sm-3">
    <div class="form-group">
      <label>{$fpl_t.weight|escape:'html':'UTF-8'}</label>
      <select name="font_weight" class="form-control">{include file='./select-options.tpl' fpl_options=$fpl_weights fpl_current=$fpl_ov.font_weight}</select>
    </div>
  </div>
  <div class="col-sm-3">
    <div class="form-group">
      <label>{$fpl_t.transform|escape:'html':'UTF-8'}</label>
      <select name="text_transform" class="form-control">{include file='./select-options.tpl' fpl_options=$fpl_transforms fpl_current=$fpl_ov.text_transform}</select>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-sm-3">
    <div class="form-group">
      <label>{$fpl_t.family|escape:'html':'UTF-8'}</label>
      <input type="text" name="font_family" class="form-control" placeholder="{$fpl_t.inherit|escape:'html':'UTF-8'}" value="{$fpl_ov.font_family|escape:'html':'UTF-8'}">
    </div>
  </div>
  <div class="col-sm-3">
    <div class="form-group">
      <label>{$fpl_t.size|escape:'html':'UTF-8'}</label>
      <input type="text" name="font_size" class="form-control" placeholder="{$fpl_t.inherit|escape:'html':'UTF-8'}" value="{$fpl_ov.font_size|escape:'html':'UTF-8'}">
      <p class="help-block">{$fpl_t.size_help|escape:'html':'UTF-8'}</p>
    </div>
  </div>
</div>
