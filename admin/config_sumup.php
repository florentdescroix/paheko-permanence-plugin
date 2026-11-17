<?php

namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Account;
use Paheko\Plugin\Perm\Perm;
use Paheko\Plugin\Perm\Sumup;

$csrf_key = sprintf('config_plugin_%s', $plugin->id);

$form->runIf('save', function () {
    $perm = Perm::getInstance();
    $sumup = Sumup::getInstance();
    $perm->saveSumupConfig((object) [
        'merchant_code' => $_POST['merchant_code'] ?? '',
        'api_key' => $_POST['api_key'] ?: $sumup->config->api_key ?? '',
        'id_reader' => $_POST['id_reader'] ?? '',
        'id_account' => $_POST['id_account'] ?? '',
        'id_bank' => $_POST['id_bank'] ?? '',
        'id_fee' => $_POST['id_fee'] ?? '',
    ]);
}, $csrf_key, './config_sumup.php');

$config = Sumup::getInstance()->config;
$sumupReaders = Sumup::getInstance()->listReaders();

$readers = [];
foreach ($sumupReaders as $reader) {
    $readers[$reader->id] = $reader->device->model->value . ' : ' . ($reader->name ?: $reader->id);
}

$bankAccounts = Years::getCurrentOpenYear()->accounts()->list(['type' => Account::TYPE_BANK]);
$expenseAccounts = Years::getCurrentOpenYear()->accounts()->list(['type' => Account::TYPE_EXPENSE]);

$bank_accounts = [];
foreach ($bankAccounts->iterate(true) as $account) {
    $bank_accounts[$account->id] = $account->label . ' (' . $account->code . ')';
}
$ex_accounts = [];
foreach ($expenseAccounts->iterate(true) as $account) {
    $ex_accounts[$account->id] = $account->label . ' (' . $account->code . ')';
}

$has_key = !empty($config->api_key);
$id_reader = $config->id_reader ?? '';
$id_account = $config->id_account ?? '';
$id_bank = $config->id_bank ?? '';
$id_fee = $config->id_fee ?? '';
$merchant_code = $config->merchant_code ?? '';

$tpl->assign(compact('csrf_key', 'readers', 'id_reader', 'bank_accounts', 'ex_accounts', 'has_key', 'id_account', 'id_bank', 'id_fee', 'merchant_code'));

$tpl->display(PLUGIN_ROOT . '/templates/config_sumup.tpl');
