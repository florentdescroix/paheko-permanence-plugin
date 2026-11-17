{include file="_head.tpl" title="Modèle économique"}

<style type="text/css">
{literal}
form > fieldset {
	margin-top: 1em;
	
}

fieldset.section {
	background: rgba(0, 0, 0, .05);
}

.section {
	position: relative;
	margin-bottom: .4em;
	border-radius: .4em;
	width: max-content;

	> legend > dd {
		display: inline;
	}
	
	> .children {
		padding-top: .4em;
		margin-left: 1.5em; 
		padding-left: 1.5em; 
		border-left: 1px solid grey;
	}

	> .children > .section::before {
		content: "";
		position: absolute;
		left: -1.5em;
		top: 1em;
		width: 1.5em;
		border-top: 1px solid grey;
	}
}
{/literal}
</style>

{include file="./_menu.tpl" current="config" sub_current=""}

{if $_GET.msg === 'SAVED'}
<p class="confirm block">
	Configuration enregistrée.
</p>
{/if}

{form_errors}

<div class="help block">
	<p>
		Créez des catégories de comptes ainsi que des objectifs mensuel (ou annuel) pour générer 
		un suivis économique de votre association.
	</p>
	<p>
		Pour simplifier le suivis, les comptes déja utilisé dans votre modèle sont suivis d'une astérisque (*).
	</p>
	<p>
		Les compte de frais seront comptabilisé en négatif, il est donc possible de fixer des objectifs négatifs.
	</p>
</div>

<form method="post" action="{$self_url}">
	<?php 
	$makeSection = function($categories, $stack, $level = 0) use (&$makeSection, &$account_groups)
	{ ?>
		{foreach from=$categories item="cat" key="key"}
			<fieldset class="section">
				<legend>
					{input type="text" default=$cat.label placeholder="Libellé" name="%s[$key][label]"|args:$stack}
					{input type="number" default=$cat.goal placeholder="€" name="%s[$key][goal]"|args:$stack size="4"}
					{if $level == 0}
						{input type="checkbox" default=$cat.by_year value="1" label="Annuel" name="%s[$key][by_year]"|args:$stack}
					{/if}
					{button onclick="this.parentElement.parentElement.remove();" data-icon="🗑"}
				</legend> 
				<div class="children">
					{if !empty($cat.children)}
						<?= $makeSection($cat->children, $stack . "[$key][children]", $level+1) ?>
					{/if}
					{if !empty($cat.accounts)}
						{foreach from=$cat.accounts item="acc" key="acc_key"}
							<div class="section">
								{input 
									type="select_groups" 
									default=$acc name="%s[$key][accounts][%s]"|args:$stack:$acc_key
									default_empty="--- Sélectionner un compte ---"
									options=$account_groups
								}
								{button onclick="this.parentElement.remove();" data-icon="🗑"}
							</div>
						{/foreach}
					{/if}
				</div>
				<div class="actions">
					{button type="submit" value="" name="%s[$key][children][]"|args:$stack data-icon="+" label="Ajouter une sous-catégorie"}
					{button type="submit" value="" name="%s[$key][accounts][]"|args:$stack data-icon="+" label="Ajouter un compte"}
				</div>
			</fieldset>
		{/foreach}
	<?php };
	$makeSection = $makeSection->bindTo($this); // template system uses '$this' inside the function
	$makeSection($categories, "categories");
?>

	<p>
		{button type="submit" name="categories[]" value="" data-icon="+" label="Ajouter une catégorie"}
	</p>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" class="main" name="save" label="Enregistrer" shape="right"}
	</p>
</form>

{include file="_foot.tpl"}