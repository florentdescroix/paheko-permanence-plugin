<?php
// Loads Paheko context
define('Paheko\WWW_URI', "localhost");
define('Paheko\WWW_URL', "http://localhost");
include __DIR__ . '/../../../include/init.php';

use KD2\DB\Date;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Plugin\Perm\HelloAsso;
use Paheko\Plugin\Perm\ModuleList;
use Paheko\Plugin\Perm\Perm;
use Paheko\Plugin\Perm\Sumup;

ob_start();
$frequence = $argv[1];

echo '[' . date('d-M-y H:i:s') . '] =========== ' . strtoupper($frequence) . " ===========\n";

$perm = Perm::getInstance();
$config = $perm->getCronConfig()->$frequence ?? null;

setlocale(LC_TIME, "fr_FR");
$monthName = date('F');

if (!isset($config)) {
    echo "Nothing to do\n\n";
    exit(0);
}

$templates = (array) ($config->templates ?? []);
if (count($templates) > 0) {
    $accounts = Years::getCurrentOpenYear()->accounts();
    $tplList = new ModuleList("transactions_templates")->asArray();
    $tplListById = [];
    foreach ($tplList as $tpl) {
        $tplListById[$tpl['id']] = $tpl;
    }
    foreach ($templates as $idTpl => $day) {
        $_GET = $tplListById[$idTpl] ?? null; // setDefaultsFromQueryString uses $_GET as arguments
        if ($_GET == null) continue;
        try {
            echo '    Transaction "' . $_GET['name'] . '" ... ';
            $transaction = new Transaction;
            $defaults = $transaction->setDefaultsFromQueryString($accounts);
            $defaults['amount'] = $defaults['amount'] / 100;
            $source = $transaction->asArray() + $defaults;
            $source['label'] .= ' (' . $monthName . ')';
            $source['simple'] = $transaction->_default_selector;
            $source['date'] = date('Y-m-' . $day);
            $source['id_year'] = Years::getMatchingOpenYearId(new Date($source['date']));
            $trans = Transactions::create($source);
            $trans->save();
            echo "OK\n";
        } catch (Throwable | Exception | Error $err) {
            echo 'ERROR => ' . $err->getMessage() . "\n";
        }
    }
}

if ($config->sumup ?? false) {
    try {
        echo "    Sumup ... ";
        Sumup::syncPayouts();
        echo "OK\n";
    } catch (Throwable | Exception | Error $err) {
        echo 'ERROR => ' . $err->getMessage() . "\n";
    }
}
if ($config->helloasso ?? false) {
    try {
        echo "    HelloAsso\n";

        echo "        Formulaires ... \n";
        HelloAsso::syncForms();
        echo "OK\n";

        echo "        Commandes ... \n";
        $orders = HelloAsso::syncOrders();
        foreach ($orders as $order) {
            if ($order->id_transaction) {
                echo "        Commande '" . $order->id . "' ... OK\n";
            } else {
                echo "        Commande '" . $order->id . "' ... FAILED\n";
            }
        }

        echo "        Payements ... \n";
        HelloAsso::syncPayouts();
        echo "OK\n";
    } catch (Throwable | Exception | Error $err) {
        var_dump($err);
        echo 'ERROR => ' . $err->getMessage() . "\n";
    }
}

echo "\n";

ob_end_flush();
