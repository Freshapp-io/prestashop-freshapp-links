{*
 * FreshApp Links
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license Proprietary - see LICENSE file
 *}
{if !empty($freshapplinks_links)}
<div class="freshapplinks-list freshapplinks-list--{$freshapplinks_hook|escape:'html':'UTF-8'} freshapplinks-list--layout-{if $freshapplinks_list.layout == 'row'}row{else}column{/if}">
  {if !empty($freshapplinks_list.name) && $freshapplinks_list.show_title}
    <p class="freshapplinks-list__title">{$freshapplinks_list.name}</p>
  {/if}
  <ul class="freshapplinks-list__items">
    {foreach from=$freshapplinks_links item=link}
      <li class="freshapplinks-list__item">
        <a class="freshapplinks-list__link"
           href="{$link._href|escape:'html':'UTF-8'}"
           {if $link.link_target == '_blank'}target="_blank" rel="noopener noreferrer"{/if}
           {if !empty($link._style)}style="{$link._style|escape:'html':'UTF-8'}"{/if}>
          {if !empty($link.icon_image)}
            <img class="freshapplinks-list__icon" src="{$module_dir}{$link.icon_image}" alt="" aria-hidden="true">
          {elseif !empty($link.icon_class)}
            <svg class="freshapplinks-list__icon" aria-hidden="true"><use href="{$freshapplinks_icons|escape:'html':'UTF-8'}#{$link.icon_class|escape:'html':'UTF-8'}"></use></svg>
          {/if}
          <span class="freshapplinks-list__label">{$link.label}</span>
          {if $link.link_target == '_blank'}
            <span class="sr-only">{l s='(opens in a new tab)' mod='freshapplinks'}</span>
          {/if}
        </a>
      </li>
    {/foreach}
  </ul>
</div>
{/if}
