<?php

namespace Paheko\Plugin\Perm;

use Dom\HTMLDocument;
use Error;
use KD2\DB\Date;
use KD2\DB\EntityManager;
use KD2\HTTP;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\DB;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Plugin\HelloAsso\Entities\Form;
use Paheko\Plugin\HelloAsso\Entities\Order;
use Paheko\Plugin\HelloAsso\HelloAsso as PluginHelloAsso;
use Paheko\Plugin\HelloAsso\Items;
use Paheko\UserException;

class HelloAsso
{
    static function list($source = null)
    {
        $source ??= $_GET;

        $ha = PluginHelloAsso::getInstance();

        $forms = EntityManager::getInstance(Form::class)->all('SELECT * FROM @TABLE');
        foreach ($forms as &$form) {
            $data = json_decode($form->raw_data);

            if ($form->state == 'Public' && (!isset($data->endDate) || new Date($data->endDate) > new Date())) {
                $list = Items::list($form);
                if (!isset($data->endDate)) {
                    $list->addConditions('AND o.date > :date');
                    $list->setParameter('date', date('Y-m-01'));
                }
                $list->setModifier(function ($row) use ($ha) {
                    if (isset($row->custom_fields)) {
                        $map = PluginHelloAsso::PAYER_FIELDS;
                        $customFields = json_decode($row->custom_fields, true);
                        $data = ['firstName' => $customFields[$map['firstName']], 'lastName' => $customFields[$map['lastName']]];
                        $matching_user = $ha->findMatchingUser((object) $data);
                        if (isset($matching_user)) {
                            $row->matching_user = User::get($matching_user->id);
                        }
                        unset($row->custom_fields);
                    }
                });

                $items = $list->asArray();
                $form = (object)$form->asArray();
                $form->tiers = [];
                unset($form->raw_data);
                foreach ($items as $item) {
                    if (empty($item->id_tier)) {
                        continue;
                    }
                    $item->name = $form->name;
                    $form->tiers[$item->id_tier] ??= (object)[
                        'id' => $item->id_tier,
                        'label' => $item->label,
                        'items' => []
                    ];
                    $form->tiers[$item->id_tier]->items[] = $item;
                }
            }
        }
        return array_filter($forms, fn($form) => !empty($form->tiers));
    }

    /**
     * Sync forms and link all of them to accountancy
     */
    static function syncForms()
    {
        $ha = PluginHelloAsso::getInstance();
        $ha->sync(true, false);

        $em = EntityManager::getInstance(Form::class);
        $forms = $em->all('SELECT * FROM @TABLE WHERE state LIKE :state', [
            'state' => Form::STATES['Public']
        ]);
        foreach ($forms as $form) {
            $data = json_decode($form->raw_data);
            if ($form->type == Form::TYPES['Event']) {
                $form->id_year = Years::getMatchingOpenYearId(new Date($data->startDate));
            } else {
                $form->id_year = Years::getMatchingOpenYearId(new Date());
            }
            if ($form->type == Form::TYPES['Membership']) {
                $form->create_payer_user = PluginHelloAsso::CREATE_UPDATE_USER;
            } else {
                $form->create_payer_user = PluginHelloAsso::UPDATE_USER;
            }
            $form->save();
        }

        return $forms;
    }

    /**
     * Sync orders and create users and transactions if needed
     */
    static function syncOrders()
    {
        $config = Perm::getInstance()->getHAConfig();

        $ha = PluginHelloAsso::getInstance();
        $ha->sync(false, true);

        $em = EntityManager::getInstance(Order::class);
        $orders = $em->all('SELECT * FROM @TABLE WHERE status = :status AND id_transaction IS NULL AND date >= :date', [
            'status' => Order::STATUS_PAID,
            'date' => Years::get(1)->start_date
        ]);

        foreach ($orders as $order) {
            $order->importAll(1);
            //$order->importAll(1); // twice : if the payer had just been created as user
        }

        $config->last_sync = date('Y-m-d');
        Perm::getInstance()->saveHAConfig($config);

        return $orders;
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
        $config = Perm::getInstance()->getHAConfig();
        $lastSync = DB::getInstance()->firstColumn('SELECT MAX(date) FROM acc_transactions WHERE label LIKE "Virement HelloAsso";');
        $lastSync ??= '1970-01-01';
        $startDate = new Date(date('Y-m-d', strtotime($lastSync . ' +1 day')));

        $monthsSince = ((new Date('now'))->getTimestamp() - $startDate->getTimestamp()) / (30 * 24 * 60 * 60);
        $pageSize = intval($monthsSince) + 2;

        self::refreshToken();
        $resp = (new HTTP)->GET("https://api.helloasso.com/v5/organizations/a-la-bricole/cash-out/history?pageSize=$pageSize", [
            'Authorization' => sprintf('Bearer %s', $config->oauth->access_token)
        ]);
        $payouts = json_decode($resp->body)->data;

        $transactions = [];

        foreach ($payouts as $payout) {
            $date = new Date($payout->creationDate);
            if ($date > $startDate) {
                $idYear = Years::getMatchingOpenYearId($date);
                $from = new Date($payout->startDate);
                $to = new Date($payout->endDate);

                $transaction = new Transaction;
                $transaction->importForm([
                    'date' => $date,
                    'reference' => 'HELLOASSO ' . $payout->id . ' ' . $date->format('d-m-Y'),
                    'notes' => 'Du ' . $from->format('d/m/Y') . ' au ' . $to->format('d/m/Y'),
                    'label' => "Virement HelloAsso",
                    'type' => Transaction::TYPE_TRANSFER,
                    'lines' => [
                        [
                            'id_account' => $config->id_account,
                            'debit' => 0,
                            'credit' => $payout->totalAmount
                        ],
                        [
                            'id_account' => $config->id_bank,
                            'debit' => $payout->totalAmount,
                            'credit' => 0,
                        ],
                    ]
                ]);
                $transaction->id_year = $idYear;
                $transaction->id_creator = 1;
                $transactions[] = $transaction;
            }
        }
        return $transactions;
    }

    static private function refreshToken($refreshToken = null)
    {
        $config = Perm::getInstance()->getHAConfig();
        $refreshToken ??= $config->oauth->refresh_token ?? null;

        if ($refreshToken == null) throw new UserException("Besoin de se reconnecter à HelloAsso");

        // access_token is still valid
        if (!empty($config->oauth->expiry) && $config->oauth->expiry > time() - 10) return;

        $resp = (new HTTP)->POST('https://api.helloasso.com/oauth2/token', [
            "grant_type" => "refresh_token",
            "refresh_token" => $refreshToken
        ]);

        $data = json_decode($resp->body);
        if (!isset($data->access_token)) throw new UserException("Besoin de se reconnecter à HelloAsso");

        $config->oauth = (object)[
            'access_token' => $data->access_token,
            'refresh_token' => $data->refresh_token,
            'expiry' => time() + $data->expires_in
        ];
        Perm::getInstance()->saveHAConfig($config);
    }

    static function login($login, $password)
    {
        $http = new HTTP();
        $resp = $http->GET("https://api.helloasso.com/oauth2/oidc/login");

        $dom = HTMLDocument::createFromString($resp->body, LIBXML_NOERROR);
        $form = $dom->getElementById('kc-form-login');
        if ($form == null)
            throw new UserException('Erreur serveur');

        $formUrl = $form->getAttribute("action");
        $data = [
            'username' => $login,
            'password' => $password,
            'rememberMe' => 'on',
            'login' => ''
        ];
        $resp = $http->POST($formUrl, $data);

        $dom = HTMLDocument::createFromString($resp->body, LIBXML_NOERROR);
        $form = $dom->getElementsByTagName('form')->item(0);
        if ($form == null)
            throw new UserException('Données d\'authentification invalides');

        $formUrl = $form->getAttribute("action");
        $inputs = $dom->getElementsByTagName('input');
        $data = [];
        foreach ($inputs as $input) {
            $data[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        $resp = $http->POST($formUrl, $data);

        if (!isset($http->cookies['rm5-HelloAsso']))
            throw new UserException('Données d\'authentification invalides');

        self::refreshToken($http->cookies['rm5-HelloAsso']);
    }
}
