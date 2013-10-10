{include file="header.tpl"}
{if $pagedescription}
  <p class="intro">{$pagedescription}</p>
{elseif $pagedescriptionhtml}
  {$pagedescriptionhtml|safe}
{/if}
<div class="tabswrap">
    <ul class="in-page-tabs">
        <li {if $exporttarget=="local"}class="current-tab"{/if}><a {if $exporttarget=="local"}class="current-tab"{/if} href="{$WWWROOT}export">Local</a></li>
        <li {if $exporttarget=="remote"}class="current-tab"{/if}><a {if $exporttarget=="remote"}class="current-tab"{/if} href="{$WWWROOT}export/remote.php">Remote</a></li>
    </ul>
</div>
<div class="subpage rel cl">
{$form|safe}
</div>
{include file="footer.tpl"}