<?php

namespace Paheko\Plugin\Perm;

use KD2\DB\Date;
use Paheko\Accounting\Accounts;
use Paheko\Accounting\Reports;
use Paheko\Accounting\Years;
use Paheko\Accounting\Transactions;
use Paheko\DB;
use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Transaction;
use Paheko\UserException;
use Paheko\Utils;
use SumUp\Exception\SDKException;
use SumUp\Shared\TransactionBaseStatus;

class Accounting
{
    static function accounts()
    {
        $out = [];
        $types = Account::COMMON_TYPES;

        $groups = Years::getCurrentOpenYear()->accounts()->listCommonGrouped([]);
        $reports = Reports::getAccountsBalances(['type_or_bookmark' => $types], 'type, code COLLATE NOCASE', false);

        foreach ($groups as $group) {
            foreach ($group->accounts as $account) {
                $report = array_find($reports, fn($a) => $a->id == $account->id);
                $acc = $account->asArray();
                $acc['debit'] = $report->debit ?? 0;
                $acc['credit'] = $report->credit ?? 0;
                $acc['balance'] = $report->balance ?? 0;
                $out[] = $acc;
            }
        }
        return $out;
    }

    static function templates()
    {
        $out = [];
        $list = new ModuleList("transactions_templates")->asArray();
        $accounts = Years::getCurrentOpenYear()->accounts();
        foreach ($list as $_GET) { // setDefaultsFromQueryString uses $_GET as parameters
            $transaction = new Transaction;
            $defaults = $transaction->setDefaultsFromQueryString($accounts);
            $defaults['amount'] = $defaults['amount'] / 100;
            $tOut = $transaction->asArray();
            $tOut['simple'] = $transaction->_default_selector;
            $out[] = $tOut + $defaults;
        }
        usort($out, fn($a, $b) => $a['type'] < $b['type'] ? -1 : 1);
        return $out;
    }

    static function transactions($query = null)
    {
        $query = $_GET;

        $list = Transactions::listByType(Years::getCurrentOpenYearId(), $query['type'] ?? null);
        $list->addColumn('line_label', Account::LIST_COLUMNS['line_label']);
        $list->addColumn('line_reference', Account::LIST_COLUMNS['line_reference']);
        $list->addColumn('debit', Account::LIST_COLUMNS['debit']);
        $list->addColumn('credit', Account::LIST_COLUMNS['credit']);
        $list->addColumn('id_account', ['select' => 'l.id_account']);
        if (isset($query['date'])) {
            $list->addConditions('AND t.date = :date');
            $list->setParameter('date', $query['date']);
        }
        if (isset($query['status'])) {
            $list->addConditions('AND t.status = :status');
            $list->setParameter('status', $query['status']);
        }
        $list->setPageSize(null);
        $list->groupBy('id_line');

        $out = [];
        $transaction = null;
        foreach ($list->iterate() as $row) {
            if (null !== $transaction && $transaction->id != $row->id) {
                $out[] = $transaction;
                $transaction = null;
            }
            if ($transaction == null) {
                $transaction = (object) [
                    'id'        => $row->id,
                    'type'      => $row->type,
                    'status'    => $row->status,
                    'label'     => $row->label,
                    'date'      => $row->date->format('Y-m-d'),
                    'reference' => $row->reference,
                    'lines'     => [],
                ];
                if ($transaction->type == Transaction::TYPE_CREDIT) {
                    $transaction->paid = self::getPaidAmountForInvoice($transaction);
                }
            }
            $transaction->lines[] = (object) [
                'label'         => $row->line_label,
                'reference'     => $row->line_reference,
                'id_account'    => $row->id_account,
                'credit'        => $row->credit,
                'debit'         => $row->debit,
            ];
        }
        if (isset($transaction)) {
            $out[] = $transaction;
        }

        return $out;
    }

    static function transaction($id)
    {
        $transaction = Transactions::get($id);
        $out = $transaction->asArray();
        $out['lines'] = $transaction->getLinesList()->asArray();
        $out['date'] = $out['date']->format('Y-m-d');
        return $out;
    }

    static function types()
    {
        $transaction = new Transaction();
        $types = $transaction->getTypesDetails();
        $types[99] = [
            'id' => 99,
            'label' => 'Règlement de créance',
            'accounts' => [
                [
                    'types' => [4],
                    'label' => 'Compte de tiers',
                    'direction' => 'credit',
                ],
                [
                    'types' => [1, 2, 3],
                    'label' => 'Compte de règlement',
                    'direction' => 'debit',
                ]
            ]
        ];
        return $types;
    }

    static function save($source = null)
    {
        $source ??= $_POST;

        // If date not set, set to now
        $source['date'] = new Date($source['date'] ?? 'now');
        $id_year = Years::getMatchingOpenYearId($source['date']);
        // Set type
        $source['type'] = count($source['lines'] ?? []) <= 2 && !empty($source['type']) ? $source['type'] : Transaction::TYPE_ADVANCED;

        if (!DB::getInstance()->inTransaction()) {
            DB::getInstance()->begin();
        }

        try {
            if (!empty($source['subscription'])) { // Transaction for a subscription
                // Create subscription
                $su = User::subscribe($source['subscription']);
                $source['account_selector'] = Accounts::getSelector($source['lines'][0]['id_account']);
                $transaction = $su->addPayment($source['id_creator'], $source); // Do save
                $su->paid = true;
                $su->save();
            } else if (!empty($source['linked_transactions'])) { // Transaction to pay invoice
                if (count($source['linked_transactions']) != count($source['lines']) - 1) {
                    throw new UserException("Il n\'est pas possible de régler à la fois des créances et autre chose", 400);
                }

                if (count($source['lines']) == 2) {
                    $source['amount'] = ($source['lines'][0]['credit'] ?? 0) + ($source['lines'][0]['debit'] ?? 0);
                }
                $paymentLine = array_find($source['lines'], fn($l) => empty($l['linked_transaction']));
                $source['payoff_account'] = [$paymentLine['id_account'] => null];
                $source['type'] = 99;
                unset($source['label']);

                $payoff = Transactions::createPayoffFrom($source['linked_transactions'], Years::get($source['id_year']));
                $transaction = $payoff->transaction;
                $transaction->id_year = $id_year;
                $transaction->id_creator = $source['id_creator'];
                $transaction->importFromPayoffForm($payoff, $source);
                // Reset lines amount
                foreach ($transaction->getLines() as $k => &$line) {
                    $line->credit = Utils::moneyToInteger($source['lines'][$k]['credit'] ?? 0);
                    $line->debit = Utils::moneyToInteger($source['lines'][$k]['debit'] ?? 0);
                }
                $transaction->save();
                $transaction->updateLinkedTransactions(array_keys($payoff->transactions));

                foreach ($payoff->transactions as $invoice) {
                    if (self::getPaidAmountForInvoice($invoice) >= $invoice->sum()) {
                        $invoice->markPaid();
                        $invoice->save();
                    }
                }
            } else { // Normal transaction
                foreach ($source['lines'] ?? [] as &$line) {
                    $credit = $line['credit'] ?? 0;
                    $debit = $line['debit'] ?? 0;
                    $line['credit'] = max(0, $credit, -$debit);
                    $line['debit'] = max(0, $debit, -$credit);
                }
                $transaction = new Transaction;
                $transaction->id_year = $id_year;
                $transaction->id_creator = $source['id_creator'];
                $transaction->importForm($source);
                $transaction->save();
            }

            // Uses SumUp only if needed, otherwise return true
            $status = Sumup::getInstance()->checkout($transaction);
            if ($status != TransactionBaseStatus::SUCCESSFUL) {
                DB::getInstance()->rollback();
                if ($status == TransactionBaseStatus::CANCELLED) {
                    throw new UserException("L'ppération a été annulée.", 400);
                } else if ($status == TransactionBaseStatus::FAILED) {
                    throw new UserException("L'opération a échoué.", 400);
                } else if ($status == TransactionBaseStatus::PENDING) {
                    throw new UserException("Le temps d'attente écoulé.", 400);
                }
                throw new UserException($status->value, 400);
            }
        } catch (SDKException $err) {
            DB::getInstance()->rollback();
            throw new UserException($err->getMessage(), 400);
        } catch (UserException $err) {
            DB::getInstance()->rollback();
            throw new UserException($err->getMessage(), 400);
        }

        DB::getInstance()->commit();

        $out = $transaction->asArray();
        if (!empty($su)) {
            $out['subscription'] = $su->asArray(true);
        }
        return $out;
    }

    static function createAccount($source = null)
    {
        $source ??= $_POST;

        $accouts = Years::getCurrentOpenYear()->accounts();
        $account = $accouts->createAuto($source['code'], $source['label']);
        $account->description = $source['description'];
        $account->bookmark = true;
        $letter = $account->getNewNumberAvailable($source['code']);
        $number = Utils::alpha2num($letter);
        $account->code = $account->code . str_pad($number + 1, 3, '0', STR_PAD_LEFT);
        $account->save();
        return $account;
    }

    static function years()
    {
        return iterator_to_array(Years::listWithStats());
    }

    static function getPaidAmountForInvoice($source)
    {
        // Dummy transaction
        $invoice = new Transaction;
        $invoice->id = $source->id;

        $out = 0;
        $payements = $invoice->listLinkedTransactions();
        foreach ($payements as $payement) {
            $lines = $payement->getLines();
            $line = array_find($lines, fn($l) => $l->label == $source->label);
            $out += $line->credit ?? 0;
        }
        return $out;
    }

    static function balances($criterias = null)
    {
        $criterias ??= $_GET ?? [];

        if (!empty($criterias['after'])) {
            $criterias['after'] = new Date($criterias['after']);
        }
        if (!empty($criterias['before'])) {
            $criterias['before'] = new Date($criterias['before']);
        }
        $criterias['year'] = Years::getMatchingOpenYearId($criterias['after']);

        $balances = Reports::getAccountsBalances($criterias + ['position' => [Account::REVENUE, Account::EXPENSE]]);

        $out = [];
        foreach ($balances as $balance) {
            $out[$balance->id] = $balance;
        }

        $out['total'] = Reports::getResultLine($criterias);

        // $report = Reports::getAccountsBalances($criterias);

        return $out;
    }
}
