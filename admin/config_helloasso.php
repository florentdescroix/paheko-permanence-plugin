<?php

namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Account;
use Paheko\Plugin\Perm\HelloAsso;
use Paheko\Plugin\Perm\Perm;

$csrf_key = sprintf('config_plugin_%s', $plugin->id);

$perm = Perm::getInstance();
$config = $perm->getHAConfig();

$form->runIf('save', function () use ($perm) {
    $perm->saveHAConfig((object) [
        'id_account' => $_POST['id_account'],
        'id_bank' => $_POST['id_bank'],
    ]);
}, $csrf_key, 'config_helloasso.php');

$login_success = null;

$error = null;
$form->runIf('login', function () use (&$error) {
    try {
        HelloAsso::login($_POST['username'], $_POST['password']);
    } catch (UserException $err) {
        $error = $err->getMessage();
    }
}, $csrf_key);

$bankAccounts = Years::getCurrentOpenYear()->accounts()->list(['type' => Account::TYPE_BANK]);

$bank_accounts = [];
foreach ($bankAccounts->iterate(true) as $account) {
    $bank_accounts[$account->id] = $account->label . ' (' . $account->code . ')';
}

$id_account = $config->id_account ?? '';
$id_bank = $config->id_bank ?? '';

$tpl->assign(compact('csrf_key', 'bank_accounts', 'id_account', 'id_bank', 'error'));

$tpl->display(PLUGIN_ROOT . '/templates/config_helloasso.tpl');
