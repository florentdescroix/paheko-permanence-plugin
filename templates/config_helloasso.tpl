{include file="_head.tpl" title="HelloAsso"}

{include file="./_menu.tpl" current="config" sub_current="helloasso"}

{form_errors}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Confiuration HelloAsso</legend>
		<p class="help">
			Ces information sont utile à l'import des donnée bancaires.
		</p>
		<dl>
			{input type="select" name="id_account" default_empty="--- Sélectionner un compte ---" default=$id_account label="Compte HelloAsso" required=true options=$bank_accounts}
			{input type="select" name="id_bank" default_empty="--- Sélectionner un compte ---" default=$id_bank label="Compte de réception des versement" required=true options=$bank_accounts}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" class="main" name="save" label="Enregistrer" shape="right"}
	</p>
</form>

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Connexion à HelloAsso</legend>
		{if isset($error)}	
			<p class="error block">
				{$error}
			</p>
		{elseif isset($_POST['save'])}
			<p class="confirm block">
				Connexion réussie.
			</p>
		{else}
			<p class="help">
				Celà est nécessaire pour importer les données de versements.
				Ces données ne sont pas sauvegardées.
			</p>
		{/if}
		<dl>
			{input type="text" name="username" label="Email" required=true}
			{input type="password" name="password" label="Mot de passe" required=true}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" class="main" name="login" label="Connexion" shape="right"}
	</p>
</form>

{include file="_foot.tpl"}
