<?php

namespace Paheko;

use Paheko\Plugin\Perm\ModuleList;
use Paheko\Plugin\Perm\Perm;

$csrf_key = sprintf('config_plugin_%s', $plugin->id);

$perm = Perm::getInstance();
$config = $perm->getCronConfig();

$form->runIf('save', function () use ($perm, $config) {
    if (isset($_POST['daily'])) {
        $config->daily = (object) $_POST['daily'];
    }
    if (isset($_POST['monthly'])) {
        $config->monthly = (object) $_POST['monthly'];
    }
    $perm->saveCronConfig($config);
}, $csrf_key, Utils::getSelfURI());

$daily = $config->daily ?? new \stdClass;
$monthly = $config->monthly ?? new \stdClass;

$templates = new ModuleList("transactions_templates")->asArray();
usort($templates, fn($a, $b) => $a['name'] < $b['name'] ? -1 : 1);

$days = [];
for ($i = 1; $i < 29; $i++) {
    $val = str_pad($i, 2, 0, STR_PAD_LEFT);
    $days[$val] = $val;
}

$tpl->assign(compact('csrf_key', 'templates', 'days', 'daily', 'monthly'));
$tpl->display(PLUGIN_ROOT . '/templates/cron.tpl');
