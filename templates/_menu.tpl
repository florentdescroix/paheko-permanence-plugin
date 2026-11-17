<nav class="tabs">
	<ul>
		<li{if $current == 'home'} class="current"{/if}><a href="./">Application</a></li>
		<li{if $current === 'cron'} class="current"{/if}><a href="cron.php">Tâches auto.</a></li>
		<li{if $current == 'import'} class="current"{/if}><a href="import.php">Import comptable</a></li>
		<li{if $current == 'config'} class="current"{/if}><a href="config.php">Configuration</a></li>
	</ul>

	{if $current === 'config'}
		<ul class="config">
			<li{if $sub_current === ''} class="current"{/if}><a href="config.php">Modèle économique</a></li>
			<li{if $sub_current === 'sumup'} class="current"{/if}><a href="config_sumup.php">SumUp</a></li>
			<li{if $sub_current === 'helloasso'} class="current"{/if}><a href="config_helloasso.php">HelloAsso</a></li>
		</ul>
	{/if}
</nav>
