{include file="_head.tpl" title="Import comptable"}

{include file="./_menu.tpl" current="import"}

{if $helloasso_disconnected}	
    <p class="alert block">
        Le compte HelloAsso est déconnecté, les données ne pouront pas êtres importées.
        <br>
        {linkButton class="main" href="./config_helloasso.php" label="Se reconnecter" shape="right"} 
    </p>
{/if}

{foreach from=$transactions key="title" item="list"}
<div style="display: inline-block">
    <h2 style="width: max-content">{$title}</h2>

    <table class="list auto">
        <thead class="userOrder">
            <tr>
                <th>Date</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
        {foreach from=$list item="transaction"}
            <tr>
                <td>{$transaction.date|date_short}</td>
                <td class="money">{$transaction->sum()|raw|money}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>
{/foreach}

<form method="post" action="{$self_url}">
    {csrf_field key=$csrf_key}
    {button type="submit" class="main" name="save" label="Importer" shape="right"}
</form>

{include file="_foot.tpl"}