<?php

namespace Paheko\Plugin\Perm;

use KD2\DB\Date;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\DB;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Utils;
use SumUp\Exception\ApiException;
use SumUp\Exception\ConfigurationException;
use SumUp\Exception\ConnectionException;
use SumUp\Services\PayoutsListParams;
use SumUp\Services\TransactionsGetParams;
use \SumUp\Shared\TransactionBaseStatus;
use SumUp\Types\FinancialPayoutStatus;
use SumUp\Types\FinancialPayoutType;

class Sumup
{
    const VENDOR_ROOT = \Paheko\SHARED_CACHE_ROOT . '/sumup-php';

    public ?\stdClass $config = null;

    protected \SumUp\SumUp $api;

    static protected $_instance = null;

    static public function getInstance(): Sumup
    {
        return static::$_instance ?: static::$_instance = new static;
    }

    protected function __construct()
    {
        self::enableDependencies();
        $perm = Perm::getInstance();
        $this->config = $perm->getSumupConfig();
        $this->api = new \SumUp\SumUp($this->config->api_key ?? null);
    }

    public function checkLogin()
    {
        try {
            // random connexion
            $this->api->roles()->list($this->config->merchant_code);
        } catch (ApiException $err) {
            $perm = Perm::getInstance();
            $this->config->api_key = '';
            $perm->saveAccConfig((array) $this->config);
            $this->api = new \SumUp\SumUp();
        }
    }

    public function checkout(Transaction $transaction): TransactionBaseStatus
    {
        $accId = $this->config->id_account;
        $total = 0;
        foreach ($transaction->getLines() as $line) {
            if ($line->debit && $line->id_account == $accId) {
                $total += $line->debit;
            }
        }

        if ($total == 0) {
            return TransactionBaseStatus::SUCCESSFUL;
        }

        $result = $this->api->readers()->createCheckout($this->config->merchant_code, $this->config->id_reader, [
            'description' => $transaction->label,
            'total_amount' => [
                'currency' => 'EUR',
                'minor_unit' => 2,
                'value' => $total,
            ],
        ]);
        $params = new TransactionsGetParams();
        $params->clientTransactionId = $result->data->clientTransactionId;

        $status = TransactionBaseStatus::PENDING;
        $timeout = 5 * 60; // 2 minutes
        $time = microtime(true);
        while ($status == TransactionBaseStatus::PENDING && $timeout > 0) {
            sleep(1);
            try {
                $transaction = $this->api->transactions()->get($this->config->merchant_code, $params);
                $status = TransactionBaseStatus::from($transaction->status);
            } catch (ApiException $_) {
                // Can happen if SumUp server takes more than 1s to react.
                // Ignore
            }
            $timeout -=  microtime(true) - $time;
            $time = microtime(true);
        }
        if ($status == TransactionBaseStatus::PENDING) {
            $this->api->readers()->terminateCheckout($this->config->merchant_code, $this->config->id_reader);
        }
        return $status;
    }

    function listReaders()
    {
        try {
            $result = $this->api->readers()->list($this->config->merchant_code)->items;
        } catch (ApiException | ConfigurationException | ConnectionException $_) {
            $result = [];
        }
        return $result;
    }

    static function syncPayouts()
    {
        $transactions = self::getPayoutTransactions();
        foreach ($transactions as $transaction) {
            $transaction->save();
        }
        return $transactions;
    }

    static function getPayoutTransactions()
    {
        $self = self::getInstance();
        $lastSync = DB::getInstance()->firstColumn('SELECT MAX(date) FROM acc_transactions WHERE label LIKE "Virement SumUp";');
        $lastSync ??= '1970-01-01';

        $params = new PayoutsListParams();
        $params->limit = 9999;
        $params->endDate = date('Y-m-d');
        $params->startDate = date('Y-m-d', strtotime($lastSync . ' +1 day'));
        if ($params->endDate < $params->startDate) {
            $params->endDate = $params->startDate;
        }

        $payouts = $self->api->payouts()->list($self->config->merchant_code, $params);

        $transactions = [];

        // Create transactions, grouped by references
        foreach ($payouts as $payout) {
            if ($payout->status !=  FinancialPayoutStatus::SUCCESSFUL) continue;
            if ($payout->type != FinancialPayoutType::PAYOUT) continue;

            $idYear = Years::getMatchingOpenYearId(new Date($payout->date));

            if (!isset($transactions[$payout->reference])) {
                $transaction = new Transaction;
                $transaction->importForm([
                    'date' => $payout->date,
                    'reference' => $payout->reference,
                    'label' => "Virement SumUp",
                    'type' => Transaction::TYPE_ADVANCED,
                    'lines' => [
                        [
                            'id_account' => $self->config->id_account,
                            'debit' => 0,
                            'credit' => 0 // here
                        ],
                        [
                            'label' => 'Frais',
                            'id_account' => $self->config->id_fee,
                            'debit' => 0, // here
                            'credit' => 0,
                        ],
                        [
                            'id_account' => $self->config->id_bank,
                            'debit' => 0, // here
                            'credit' => 0,
                        ],
                    ]
                ]);
                $transaction->id_year = $idYear;
                $transaction->id_creator = 1;
                $transactions[$payout->reference] = $transaction;
            }

            $transactions[$payout->reference]->getLines()[0]->credit += Utils::moneyToInteger($payout->amount + $payout->fee);
            $transactions[$payout->reference]->getLines()[1]->debit += Utils::moneyToInteger($payout->fee);
            $transactions[$payout->reference]->getLines()[2]->debit += Utils::moneyToInteger($payout->amount);
        }

        return array_values($transactions);
    }

    static public function enableDependencies()
    {
        static $enabled = false;

        if ($enabled) {
            return;
        }

        self::installDependencies();

        spl_autoload_register(function (string $name) {
            $path = substr($name, 6);
            $path = self::VENDOR_ROOT . '/src/' . str_replace('\\', '/', $path) . '.php';

            if (!file_exists($path)) {
                if (str_contains($path, 'Services')) {
                    $path = preg_replace('/Services\/([A-Z][a-z]*).*/', '$1/$1.php', $path);
                } else if (str_contains($path, 'Shared')) {
                    $path = preg_replace('/Shared\/(.*)/', '/Shared/Shared.php', $path);
                }
            }

            if (file_exists($path)) {
                require $path;
            } else {
                //die($path);
            }
        });

        $enabled = true;
    }

    static public function installDependencies($force = false)
    {
        $root = self::VENDOR_ROOT;
        if ($force) {
            exec(sprintf('rm -rf %s', $root));
        }
        if (file_exists($root)) {
            return;
        }

        $tmp = '/tmp/sumup';
        exec(sprintf('git clone https://github.com/sumup/sumup-php.git %s', $tmp));
        Utils::safe_mkdir($root);
        exec(sprintf('mv %s %s', $tmp . '/src', $root));
        exec(sprintf('rm -rf %s', $tmp));
    }
}
