<?php

namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Account;
use Paheko\Plugin\Perm\Perm;

$csrf_key = sprintf('config_plugin_%s', $plugin->id);

$perm = Perm::getInstance();

$form->runIf('add_section', function () use ($sumup) {
    // $perm->saveAccConfig($_POST['merchant_code'] ?? '');
}, $csrf_key, './config_acc.php');

$form->runIf('add_category', function () use ($sumup) {
    // $perm->saveAccConfig($_POST['merchant_code'] ?? '');
}, $csrf_key, './config_acc.php');

$revenueAccounts = Years::getCurrentOpenYear()->accounts()->list(['type' => Account::TYPE_REVENUE]);
$expenseAccounts = Years::getCurrentOpenYear()->accounts()->list(['type' => Account::TYPE_EXPENSE]);

$re_accounts = [];
foreach ($revenueAccounts->iterate(true) as $account) {
    $bank_accounts[$account->id] = $account->label . ' (' . $account->code . ')';
}
$ex_accounts = [];
foreach ($expenseAccounts->iterate(true) as $account) {
    $ex_accounts[$account->id] = $account->label . ' (' . $account->code . ')';
}

$has_key = !empty($sumup->get('api_key'));

$tpl->assign(compact('csrf_key', 'accounts', 'k'));

$tpl->display(PLUGIN_ROOT . '/templates/config_sumup.tpl');
