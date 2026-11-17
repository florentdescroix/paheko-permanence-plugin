<details open>
    <summary>
        {input type="text" default=$item.label placeholder="Libellé" name="%s[label]"|args:$stack}
        {input type="number" default=$item.goal placeholder="Objectif" name="%s[goal]"|args:$stack}
        {button onclick="this.parentElement.parentElement.remove();" data-icon="🗑"}
    {if !empty($item.children)}
        {button type="submit" value="" name="%s[children][%s]"|args:$stack:count($item->children) label="Ajouter une sous-catégorie"}
    {else}
        {button type="submit" value="" name="%s[children][%s]"|args:$stack:0 label="Ajouter une sous-catégorie"}
    {/if}
    </summary>
    {if !empty($item.children)}
        <div style="margin-left: 1.5em; padding-left: 2em; border-left: 1px solid grey">
            {foreach from=$item.children item="child" key="key"}
                {include file="./_config_acc_line.tpl" item=$child stack="%s[children][%s]"|args:$stack:$key}
            {/foreach}
        </div>
    {/if}
</details>
