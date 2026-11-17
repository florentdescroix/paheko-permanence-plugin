<?php

namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Account;
use Paheko\Plugin\Perm\Perm;

$csrf_key = sprintf('config_plugin_%s', $plugin->id);

$perm = Perm::getInstance();

$form->runIf('categories', function () use ($perm) {
	$perm->saveAccConfig($_POST['categories']);
}, $csrf_key, './config.php');

function checkUsed($category, &$used_accounts)
{
	foreach ($category->accounts ?? [] as $id_account) {
		$used_accounts[] = $id_account ?? null;
	}
	foreach ($category->children ?? [] as &$child) {
		checkUsed($child, $used_accounts);
	}
}

$categories = $perm->getAccConfig();

$used_accounts = [];
foreach ($categories as &$category) {
	checkUsed($category, $used_accounts);
}

$account_groups = [];
$groups = Years::getCurrentOpenYear()->accounts()->listCommonGrouped([]);

foreach ($groups as $key => &$group) {
	if (in_array($key, [Account::TYPE_EXPENSE, Account::TYPE_REVENUE])) {
		$account_group = [
			'label' => $group->label,
			'options' => []
		];
		foreach ($group->accounts as &$account) {
			$account_group['options'][$account->id] = $account->label . (in_array($account->id, $used_accounts) ? '*' : '');
		}
		$account_groups[] = $account_group;
	}
}

$tpl->assign(compact('csrf_key', 'categories', 'account_groups'));

$tpl->display(PLUGIN_ROOT . '/templates/config.tpl');
