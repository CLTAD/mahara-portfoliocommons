{include file="header.tpl"}

    <p>{str tag="usersearchinstructions" section="admin"}</p>
    <div id="initials">
    <div id="firstnamelist">
      <label>{str tag="firstname"}:</label>
       <span class="first-initial{if !$search->f} selected{/if} all">
        <a href="{$WWWROOT}admin/users/search.php?query={$search->query}{if $search->l}&amp;l={$search->l}{/if}{if $search->sortby}&amp;sortby={$search->sortby}{/if}{if $search->sortdir}&amp;sortdir={$search->sortdir}{/if}{if $limit}&amp;limit={$limit}{/if}">{str tag="All"}</a>
       </span>
       {foreach from=$alphabet item=a}
       <span class="first-initial{if $a == $search->f} selected{/if}">
        <a href="{$WWWROOT}admin/users/search.php?query={$search->query}&amp;f={$a}{if $search->l}&amp;l={$search->l}{/if}{if $search->sortby}&amp;sortby={$search->sortby}{/if}{if $search->sortdir}&amp;sortdir={$search->sortdir}{/if}{if $limit}&amp;limit={$limit}{/if}">{$a}</a>
       </span>
       {/foreach}
    </div>
    <div id="lastnamelist">
      <label>{str tag="lastname"}:</label>
       <span class="last-initial{if !$search->l} selected{/if} all">
        <a href="{$WWWROOT}admin/users/search.php?query={$search->query}{if $search->f}&amp;f={$search->f}{/if}{if $search->sortby}&amp;sortby={$search->sortby}{/if}{if $search->sortdir}&amp;sortdir={$search->sortdir}{/if}{if $limit}&amp;limit={$limit}{/if}">{str tag="All"}</a>
       </span>
       {foreach from=$alphabet item=a}
       <span class="last-initial{if $a == $search->l} selected{/if}">
        <a href="{$WWWROOT}admin/users/search.php?query={$search->query}&amp;l={$a}{if $search->f}&amp;f={$search->f}{/if}{if $search->sortby}&amp;sortby={$search->sortby}{/if}{if $search->sortdir}&amp;sortdir={$search->sortdir}{/if}{if $limit}&amp;limit={$limit}{/if}">{$a}</a>
       </span>
       {/foreach}
    </div>
    </div>
    {if $USER->get('admin') || $USER->is_institutional_admin() || get_config('staffreports')}
    <div class="withselectedusers">
    <strong>{str tag=withselectedusers section=admin}:</strong>&nbsp;
    {if $USER->get('admin') || $USER->is_institutional_admin()}
    <form class="nojs-hidden-inline" id="bulkactions" action="{$WWWROOT}admin/users/bulk.php" method="post">
      <input type="button" class="button" name="edit" value="{str tag=edit}">
    </form>
    {/if}
    <form class="nojs-hidden-inline" id="report" action="{$WWWROOT}admin/users/report.php" method="post">
      <input type="button" class="button" name="reports" value="{str tag=getreports section=admin}">
    </form>
    <div id="nousersselected" class="hidden error">{str tag=nousersselected section=admin}</div>
    </div>
    {/if}
    <form action="{$WWWROOT}admin/users/search.php" method="post">
        {if $search->f}
        <input type="hidden" name="f" id="f" value="{$search->f}">
        {/if}
        {if $search->l}
        <input type="hidden" name="l" id="l" value="{$search->l}">
        {/if}
        {if $search->sortby}
        <input type="hidden" name="sortby" id="sortby" value="{$search->sortby}">
        {/if}
        {if $search->sortdir}
        <input type="hidden" name="sortdir" id="sortdir" value="{$search->sortdir}">
        {/if}
        {if $limit}
        <input type="hidden" name="limit" id="limit" value="{$limit}">
        {/if}
        <div class="loggedin-filter">
            <label for="loggedin">{str tag="loggedinfilter" section="admin"}</label>
            <select name="loggedin" id="loggedin">
                {foreach from=$loggedintypes item=t}
                    <option value="{$t['name']}"{if $search->loggedin === $t['name']} selected="selected"{/if}>{$t['string']}</option>
                {/foreach}
            </select>
            <span id="loggedindate_container"{if !($search->loggedin == 'since' || $search->loggedin == 'notsince')} class="js-hidden"{/if}>
                {$loggedindate|safe}
            </span>
        </div>
        <div class="searchform-gap nojs-hidden-block"></div>
        <div class="searchform">
            <label>{str tag='Search' section='admin'}:</label>
                <input type="text" name="query" id="query"{if $search->query} value="{$search->query}"{/if}>
            
            {if count($institutions) > 1}
            <span class="institutions">
                <label>{str tag='Institution' section='admin'}:</label>
                    <select name="institution" id="institution">
                        <option value="all"{if !$.request.institution} selected="selected"{/if}>{str tag=All}</option>
                        {foreach from=$institutions item=i}
                        <option value="{$i->name}"{if $i->name == $.request.institution}" selected="selected"{/if}>{$i->displayname}</option>
                        {/foreach}
                    </select>
            </span>
            {/if}
            <button id="query-button" class="btn-search" type="submit">{str tag="go"}</button>
        </div>
        <div id="results" class="section">
            {$results|safe}
        </div>
    </form>

{include file="footer.tpl"}
