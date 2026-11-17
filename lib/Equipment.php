<?php

namespace Paheko\Plugin\Perm;

use KD2\JSONSchema;
use Paheko\DB;
use Paheko\Entities\Files\File;
use Paheko\UserException;
use Paheko\Users\DynamicFields;
use Paheko\UserTemplate\Modules;
use Paheko\Utils;

use const Paheko\ROOT;

class Equipment
{
    static function getCategories()
    {
        return new ModuleList("equipment", "category")->asArray();
    }

    static function getAll()
    {
        $list = new ModuleList("equipment", "equipment");
        $list->addColumn("category", ["select" => "json_object(c.*)"]);
        $list->addTables("LEFT JOIN @TABLE c ON c.key = @TABLE.$$.category");
        $list->orderBy("@TABLE.$$.name");

        return $list->asArray();
    }

    static function get($key, $type = 'equipment')
    {
        $list = new ModuleList("equipment", $type);
        $list->addConditions("AND @TABLE.key = :key");
        $list->setParameter("key", $key);

        if ($type == "equipment") {
            $list->addColumn("category", ["select" => "json_object(c.*)"]);
            $list->addTables("LEFT JOIN @TABLE c ON c.key = @TABLE.$$.category");
        }

        $row = $list->iterate(true)->current();
        if ($type == "equipment") {
            $row["movements"] = self::getMovementsOf($row['key']);
        }
        return $row;
    }

    static function getMovementsOf($key)
    {
        $list = new ModuleList("equipment", "movement");

        $idField = DynamicFields::getNameFieldsSQL("u");
        $list->addColumn("return", ["select" => "json_object(r.*)"]);
        $list->addColumn("user", ["select" => "json_object('id', u.id, 'identity', $idField)"]);

        $list->addTables("LEFT JOIN @TABLE l ON @TABLE.key = l.$$.temp_key");
        $list->addTables("LEFT JOIN @TABLE r ON r.key = l.$$.return");
        $list->addTables("LEFT JOIN users u ON u.id = COALESCE(@TABLE.$$.user, r.$$.user)");

        $list->addConditions("AND (@TABLE.$$.equipment = :eqKey)");
        $list->setParameter("eqKey", $key);

        $out = $list->asArray();
        $config = self::getConfig();
        foreach ($out as &$value) {
            $value['operation'] = array_find(
                $config[$value['direction'] . '_nature'],
                fn($v) => $v->key == $value['operation']
            );
        }
        return $out;
    }

    static function addMovement($key, $source = null)
    {
        $source ??= $_POST;

        $eq = self::get($key, null);
        if (empty($eq)) {
            throw new UserException("Equipement non trouvé", 404);
        }

        $mvt = [
            "type" => "movement",
            "direction" => $source["direction"],
            "operation" => $source["operation"],
            "amount" => $source["amount"] ?? 1,
            "equipment" => $eq['key'],
            "date" => date("Y-m-d"),
            "comment" => $source["comment"] ?? "",
            "user" => $source["user"] ?? null,
            "storage" => $source["storage"] ?? null,
            "transactions" => $source["transactions"] ?? null,
            "return_date" => $source["return_date"] ?? null,
        ];

        if ($source["direction"] == 'input') {
            $eq["out"] -= $mvt["amount"];
        } else {
            $eq["out"] += $mvt["amount"];
        }

        if ($eq["out"] > $eq['stock']) {
            throw new UserException("Pas assez de stock pour une sortie", 400);
        } else if ($eq["out"] < 0) {
            throw new UserException("Trop de stock pour une entrée", 400);
        }

        DB::getInstance()->begin();
        self::save($eq);
        self::save($mvt);
        if (!empty($source['links']) && is_array($source['links'])) {
            foreach ($source['links'] as $linkKey) {
                $link = [
                    "type" => "link",
                    "direction" => $source["direction"],
                    "temp_key" => $linkKey,
                    "return" => $mvt["key"],
                ];
                self::save($link);
            }
        }
        DB::getInstance()->commit();
        return $mvt;
    }

    static function save(&$el)
    {
        $schemas = [
            "equipment" => "equipment",
            "movement" => "movements/movement",
            "link" => "movements/link",
        ];
        $schema = JSONSchema::fromFile(ROOT . "/" . File::CONTEXT_MODULES . '/equipment/' . $schemas[$el["type"]] . '.schema.json');

        $document = $el;
        unset($document["key"], $document["id"]);
        $schema->validate($document);
        $document = json_encode($document);

        if (isset($el['key'])) {
            $exists = self::get($el['key'], null);
        } else {
            $el['key'] = Utils::uuid();
        }

        $db = DB::getInstance();
        if (!empty($exists)) {
            $db->update("module_data_equipment", compact("document"), "key = :key", ["key" => $exists['key']]);
        } else {
            $key = $el['key'];
            $db->insert("module_data_equipment", compact("key", "document"));
            $el["id"] = $db->lastInsertId();
        }
        return $el;
    }

    static function getConfig()
    {
        $eqModule = Modules::get('equipment');
        $file = file_get_contents(ROOT . "/" . File::CONTEXT_MODULES . '/equipment/' . 'default.json');
        $defaultConfig = json_decode($file);
        $config = $eqModule->config;

        foreach (['input', 'output'] as $direction) {
            $config->{$direction . '_nature'} ??= $defaultConfig->{$direction . 's'};
            foreach ($config->{$direction . '_nature'} as &$value) {
                $value->id = $value->key;
            }
        }

        return (array) $config;
    }
}
