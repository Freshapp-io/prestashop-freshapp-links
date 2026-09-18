{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license Proprietary - see LICENSE file
 *}
<div class="form-group">
  <label class="control-label col-lg-3">{$fpl_t.type|escape:'html':'UTF-8'}</label>
  <div class="col-lg-9">
    <select name="target_type" id="fpl-target-type" class="form-control">{include file='./select-options.tpl' fpl_options=$fpl_types fpl_current=$fpl_target_type}</select>
  </div>
</div>
