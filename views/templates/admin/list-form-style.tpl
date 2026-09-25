{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license GPL-3.0-or-later
 *}
<hr>
<p class="text-muted"><strong>{$fpl_t.intro|escape:'html':'UTF-8'}</strong></p>
<div class="row">
  <div class="col-sm-3">
    <div class="form-group">
      <label>{$fpl_t.icon_color|escape:'html':'UTF-8'}</label><br>
      {include file='./color-field.tpl' fpl_name='icon_color' fpl_value=$fpl_list.icon_color fpl_placeholder=$fpl_t.empty}
    </div>
  </div>
  <div class="col-sm-3">
    <div class="form-group">
      <label>{$fpl_t.link_color|escape:'html':'UTF-8'}</label><br>
      {include file='./color-field.tpl' fpl_name='link_color' fpl_value=$fpl_list.link_color fpl_placeholder=$fpl_t.empty}
    </div>
  </div>
  <div class="col-sm-3">
    <div class="form-group">
      <label>{$fpl_t.weight|escape:'html':'UTF-8'}</label>
      <select name="font_weight" class="form-control">{include file='./select-options.tpl' fpl_options=$fpl_weights fpl_current=$fpl_list.font_weight}</select>
    </div>
  </div>
  <div class="col-sm-3">
    <div class="form-group">
      <label>{$fpl_t.transform|escape:'html':'UTF-8'}</label>
      <select name="text_transform" class="form-control">{include file='./select-options.tpl' fpl_options=$fpl_transforms fpl_current=$fpl_list.text_transform}</select>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-sm-3">
    <div class="form-group">
      <label>{$fpl_t.family|escape:'html':'UTF-8'}</label>
      <input type="text" name="font_family" class="form-control" placeholder="{$fpl_t.family_placeholder|escape:'html':'UTF-8'}" value="{$fpl_list.font_family|escape:'html':'UTF-8'}">
      <p class="help-block">{$fpl_t.family_help|escape:'html':'UTF-8'}</p>
    </div>
  </div>
  <div class="col-sm-3">
    <div class="form-group">
      <label>{$fpl_t.size|escape:'html':'UTF-8'}</label>
      <input type="text" name="font_size" class="form-control" placeholder="{$fpl_t.empty|escape:'html':'UTF-8'}" value="{$fpl_list.font_size|escape:'html':'UTF-8'}">
      <p class="help-block">{$fpl_t.size_help|escape:'html':'UTF-8'}</p>
    </div>
  </div>
</div>
