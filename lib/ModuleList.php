<?php

namespace Paheko\Plugin\Perm;

use Override;
use Paheko\DynamicList;
use Paheko\UserTemplate\Sections;

class ModuleList extends DynamicList
{
    protected string $module;

    public function __construct(string $module, ?string $type = null)
    {
        $columns = [];
        $columns['id'] = ['select' => '@TABLE.key',];
        $columns['key'] = ['select' => '@TABLE.key'];
        $columns['document'] = ['select' => '@TABLE.document'];
        $this->module = $module;
        $this->modifier = fn(&$row) => self::extractJson($row);
        if (!empty($type)) {
            $this->setParameter('type', $type);
            parent::__construct($columns, '@TABLE ', '@TABLE.$$.type = :type');
        } else {
            parent::__construct($columns, '@TABLE ');
        }
    }

    #[Override]
    public function addColumn(string $name, array $column, int $position = -1)
    {
        if (str_starts_with($column['select'], 'json_object(')) {
            $parts = substr($column['select'],  strlen('json_object('), -1);
            $parts = explode(",", $parts);
            foreach ($parts as &$part) {
                $part = trim($part);
                if (str_contains($part, '.')) {
                    [$prefix, $key] = explode('.', $part);
                    if ($key == '*') {
                        $part = "'id', $prefix.id, 'key', $prefix.key, 'document', $prefix.document";
                    }
                }
            }
            $parts = explode(',', implode(',', $parts));
            $column['select'] = sprintf(
                "CASE WHEN %s IS NOT NULL THEN json_object(%s) ELSE null END",
                $parts[1],
                implode(',', $parts)
            );
        }
        return parent::addColumn($name, $column, $position);
    }

    #[Override]
    public function orderBy(string $key, bool $desc = false)
    {
        $name = $key;
        $parts = explode("$$.", $key);
        if (isset($parts[1])) {
            $name = $parts[1];
            $this->addColumn($name, ["select" => "$key"]);
        }
        parent::orderBy($name, $desc);
    }

    #[Override]
    function SQL(bool $count = false)
    {
        return Sections::_moduleReplaceJSONExtract(parent::SQL($count), 'module_data_' . $this->module);
    }

    static public function extractJson(&$obj)
    {
        if (is_string($obj) && json_validate($obj)) {
            $obj = json_decode($obj);
        }
        if (is_array($obj) || $obj instanceof \stdClass) {
            $obj = (array) $obj;
            foreach ($obj as $key => &$value) {
                self::extractJson($value);
                if ($key == 'document') {
                    $obj = array_merge((array) $value, $obj);
                    unset($obj[$key]);
                }
            }
        }
    }
}
