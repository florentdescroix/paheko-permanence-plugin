<?php

namespace Paheko;

use Paheko\Plugin\Perm\HelloAsso;
use Paheko\Plugin\Perm\Sumup;

$csrf_key = sprintf('config_plugin_%s', $plugin->id);

$form->runIf('save', function () {
    Sumup::syncPayouts();
    HelloAsso::syncPayouts();
}, $csrf_key);

$helloasso_disconnected = false;
try {
    $haT = HelloAsso::getPayoutTransactions();
} catch (UserException $err) {
    // Need to reconect
    $helloasso_disconnected = true;
    $haT = [];
}

$transactions = [
    "Sumup" => Sumup::getPayoutTransactions(),
    "HelloAsso" => $haT,
];

$tpl->assign(compact('csrf_key', 'transactions', 'helloasso_disconnected'));

$tpl->display(PLUGIN_ROOT . '/templates/import.tpl');
