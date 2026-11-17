<?php

namespace Paheko\Plugin\Perm;

use KD2\DB\Date;
use Paheko\DB;
use Paheko\Services\Services;

class Stats
{
    static function all()
    {
        $out = [];

        $db = DB::getInstance();

        $from = new Date($_GET['from']);
        $to = new Date($_GET['to']);
        $params = ['from' => $from, 'to' => $to];

        $out['users'] = [];

        $out['users']['value'] = $db->firstColumn(
            'SELECT count(distinct id_user) 
            FROM services_users su 
            LEFT JOIN services s ON s.id = su.id_service
            WHERE su.expiry_date >= :from AND su.date <= :to
            AND s.duration > 1',
            $params
        );
        $out['users']['new'] = $db->firstColumn(
            'SELECT count(distinct u.id)
            FROM users u
            WHERE u.date_inscription >= :from AND u.date_inscription <= :to',
            $params
        );
        $out['users']['renew'] = $db->firstColumn(
            'SELECT count (date)
            FROM (
                SELECT min(su.date) as date, u.date_inscription as date_inscription
                FROM services_users su 
                LEFT JOIN services s ON s.id = su.id_service
                LEFT JOIN users u ON su.id_user = u.id
                WHERE su.date >= :from AND su.date <= :to
                AND s.duration > 1
                GROUP BY u.id
            ) 
            WHERE date_inscription < date',
            $params
        );

        $out['users']['age'] = [];
        $out['users']['age']['list'] = $db->get(
            'SELECT age as key, count(id) as value
            FROM (
                SELECT u.id as id, (:year - u.annee_naissance) as age 
                FROM services_users su 
                LEFT JOIN users u ON su.id_user = u.id
                WHERE su.date >= :from AND su.date < :to
                AND annee_naissance IS NOT NULL
                GROUP BY u.id
            ) GROUP BY age ORDER BY age',
            $params + ['year' => intval($to->format('Y'))]
        );
        $ages = [];
        foreach ($out['users']['age']['list'] as $e) {
            for ($i = 0; $i < $e->value; $i++) {
                $ages[] = $e->key;
            }
        }
        $len = count($ages);
        $out['users']['age']['value'] = $len;
        $out['users']['age']['mean'] = array_sum(array_map(fn($e) => $e->key * $e->value, $out['users']['age']['list'])) / $len;
        if ($len % 2 == 0) {
            $out['users']['age']['median'] = $ages[intval($len / 2)];
        } else {
            $out['users']['age']['median'] = ($ages[intval(floor($len / 2))] + $ages[intval(ceil($len / 2))]) / 2;
        }

        $out['subscriptions'] = ['value' => 0, 'services' => []];
        $services = Services::listGroupedWithFees();
        foreach ($services as $service) {
            unset($subData);
            $subData = ['key' => $service->label, 'value' => 0, 'fees' => []];
            $select = '';

            foreach ($service->fees as $fee) {
                unset($feeData);
                $feeData = ['key' => $fee->label, 'value' => 0];
                $subData['fees'][] = &$feeData;
                $feeData['value'] = $db->firstColumn(
                    'SELECT count(l.id) 
                    FROM acc_transactions_lines l
                    LEFT JOIN acc_transactions t ON l.id_transaction = t.id
                    WHERE t.date >= :from AND t.date <= :to
                    AND l.credit = :credit
                    AND l.id_account = :id_account',
                    $params + ['id_account' => $fee->id_account, 'credit' => $fee->amount]
                );
            }

            $out['subscriptions']['services'][] = &$subData;
            $subData['list'] = $db->get(
                'SELECT t.date as key, count(l.id) as value ' . $select . '
                FROM acc_transactions_lines l
                LEFT JOIN acc_transactions t ON l.id_transaction = t.id
                WHERE t.date >= :from AND t.date <= :to
                AND l.id_account = :id_account
                AND l.credit > 0
                GROUP BY t.date',
                $params + ['id_account' => $service->fees[0]->id_account]
            );
            $subData['value'] = array_sum(array_map(fn($e) => $e->value, $subData['list']));
            $out['subscriptions']['value'] += $subData['value'];
        }

        return $out;
    }
}
