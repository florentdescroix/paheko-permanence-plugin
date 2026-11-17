<?php

namespace Paheko\Plugin\Perm;

use Paheko\Services\Services;
use Paheko\Services\Services_User;
use Paheko\Users\Categories;
use Paheko\Users\DynamicFields;
use Paheko\Users\Users;

class User
{
    static function getAll()
    {
        $list = Users::listByCategory();

        $list->addColumn('active', ['select' => 'COALESCE(MAX(expiry_date), 0) >= DATE()']);
        $list->addTables('LEFT JOIN services_users su ON u.id = su.id_user AND su.paid = 1');

        if (!empty($_GET['search'])) {
            $needle = strtolower($_GET['search']);
            $list->addConditions('AND identity LIKE :search');
            $list->setParameter('search', "%$needle%");
        }

        if (!empty($_GET['permissions'])) {
            $permissions = is_array($_GET['permissions']) ? $_GET['permissions'] : [$_GET['permissions']];
            $categories = Categories::listWithStats();
            $list->addConditions('AND (0 ');
            foreach ($categories as $id => $category) {
                $asPerm = array_all($permissions, function ($value) use ($category) {
                    $perm = 'perm_' . $value;
                    return !empty($category->$perm);
                });
                if ($asPerm) {
                    $list->addConditions(sprintf('OR u.id_category = %d', $id));
                }
            }
            $list->addConditions(')');
        }

        $users = $list->asArray();
        foreach ($users as &$user) {
            $user->id = $user->_user_id;
        }

        return $users;
    }

    static function get($id)
    {
        $user = Users::get($id)->asArray(true);
        unset($user['password'], $user['otp_secret'], $user['otp_recovery_codes'], $user['pgp_key']);
        foreach (DynamicFields::getInstance()->fieldsByType('multiple') as $f) {
            $value = $user[$f->name];
            $user[$f->name] = [];
            foreach ($f->options as $b => $name) {
                if ((int)$value & (0x01 << (int)$b)) {
                    $user[$f->name][] = $name;
                }
            }
        }
        $user['services'] = Services_User::listDistinctForUser($id);
        $user['active'] = array_any($user['services'], fn($s) => $s->expiry_date >= date('Y-m-d'));
        return $user;
    }

    static function fields()
    {
        $out = [];
        $fields = DynamicFields::getInstance()->allExceptPassword();
        foreach ($fields as $value) {
            $out[] = $value->asArray();
        }
        return $out;
    }

    static function save($id = null)
    {
        unset($_POST['id'], $_POST['id_category']);

        if (!empty($id)) {
            $user = Users::get($id);
        } else {
            $user = Users::create();
            $_POST['date_inscription'] = date('Y-m-d');
        }
        $user->importForm();
        $user->setNumberIfEmpty();
        $user->save();
        return User::get($user->id);
    }

    static function services()
    {
        return array_values(Services::listGroupedWithFees());
    }

    static function subscribe($source = null)
    {
        $source ??= $_POST;
        unset($source['date'], $source['expected_amount'], $source['paid']);

        $fees = Services_User::listDistinctForUser($source['id_user']);
        if (count($fees) > 0) {
            $lastFee = $fees[0];
            if ($lastFee->expiry_date > date('Y-m-d')) {
                $source['date'] = $lastFee->expiry_date;
            }
        }

        $su = Services_User::create($source['id_user'], $source['id_service'], $source['id_fee']);
        $su->importForm($source);

        $su->save();

        return $su;
    }
}
