{include file="_head.tpl" title="Automatismes"}

{include file="./_menu.tpl" current="cron"}

{if $_GET.msg === 'SAVED'}
<p class="confirm block">
	Configuration enregistrée.
</p>
{/if}

{form_errors}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Quotidien</legend>
		{if isset($daily->sumup)}
			{assign var="sumup" value=$daily->sumup}
		{else}
			{assign var="sumup" value=""}
		{/if}
		{if isset($daily->helloasso)}
			{assign var="helloasso" value=$daily->helloasso}
		{else}
			{assign var="helloasso" value=""}
		{/if}
		<dl>
			{input type="checkbox" default=$sumup name="daily[sumup]"|args value="true" label="Synchronisation SumUp"}
			{input type="checkbox" default=$helloasso name="daily[helloasso]"|args value="true" label="Synchronisation HelloAsso"}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Mensuel</legend>
		<table class="list auto">
			<thead class="userOrder">
				<tr>
					<th>Modèle d'écriture</th>
					<th>Jour du mois</th>
				</tr>
			</thead>
			<tbody>
			{foreach from=$templates item="tpl" set}
				{if isset($monthly->templates->{$tpl['id']})}
					{assign var="name" value="monthly[templates][%s]"|args:$tpl.id}
					{assign var="value" value=$monthly->templates->{$tpl.id}}
				{else}
					{assign var="name" value=""}
					{assign var="value" value=""}
				{/if}
				<tr>
					<td>
						{$tpl.name}
					</td>
					<td>
						{input type="select" name=$name default=$value options=$days onchange="this.name = this.value ? 'monthly[templates][%s]' : ''"|args:$tpl.id}
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" class="main" name="save" label="Enregistrer" shape="right"}
	</p>
</form>

{include file="_foot.tpl"}
