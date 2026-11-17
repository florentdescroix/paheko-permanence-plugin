{include file="_head.tpl" title="Sumup"}

{include file="./_menu.tpl" current="config" sub_current="sumup"}

{form_errors}

<p class="block help">
	Cette page de configuration permet d'importer les données bancaires de votre compte SumUp et de déclencher votre terminal de payement lors des payments associés.
	<br>
	<br>
	Pour renseigner ces informations, rendez-vous dans <a href="https://me.sumup.com/fr-fr/settings/api-keys" target="_blank">votre administration SumUp</a>.
	<br>
	Vous aurez peut-être besoin de créer une nouvelle clé d'API.
</p>

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Connexion SumUp</legend>
		<dl>
			{input type="text" name="merchant_code" default=$merchant_code label="Code marchand" required=true}
			{input type="password" name="api_key" value="1" label="Clé d'API"}
		</dl>

		<dl>
			{input type="select" disabled=!$has_key name="id_reader" default_empty="--- Sélectionner le lecteur ---" default=$id_reader label="Lecteur" options=$readers}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Configuration comptabilité</legend>
		<dl>
			{input type="select" required=true name="id_account" default_empty="--- Sélectionner un compte ---" default=$id_account label="Compte Sumup" options=$bank_accounts help="Ce compte déclenche le terminal de payement si associé"}
			{input type="select" required=true name="id_bank" default_empty="--- Sélectionner un compte ---" default=$id_bank label="Compte de réception des versement" options=$bank_accounts}
			{input type="select" required=true name="id_fee" default_empty="--- Sélectionner un compte ---" default=$id_fee label="Compte de frais" options=$ex_accounts}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" class="main" name="save" label="Enregistrer" shape="right"}
	</p>
</form>

{include file="_foot.tpl"}
