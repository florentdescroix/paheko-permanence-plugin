<?php

namespace Paheko\Plugin\Perm;

use DateTime;
use Paheko\Entities\Plugin;
use Paheko\Plugins;

use stdClass;

class Perm
{
    protected ?stdClass $config = null;
    protected ?Plugin $plugin = null;

    static protected $_instance = null;

    static public function getInstance(): Perm
    {
        return static::$_instance ?: static::$_instance = new static;
    }

    protected function __construct()
    {
        $this->plugin = Plugins::get('perm');
    }

    public function getAccConfig(): array
    {
        return $this->plugin->getConfig('accounts') ?? [];
    }

    public function saveAccConfig(array $config): void
    {
        $this->plugin->setConfigProperty('accounts', $config);
        $this->plugin->save();
    }

    public function getSumupConfig(): stdClass
    {
        return $this->plugin->getConfig('sumup') ?? new stdClass;
    }

    public function saveSumupConfig(stdClass $config): void
    {
        $this->plugin->setConfigProperty('sumup', $config);
        $this->plugin->save();
    }

    public function getHAConfig(): stdClass
    {
        return $this->plugin->getConfig('helloasso') ?? new stdClass;
    }

    public function saveHAConfig(stdClass $config): void
    {
        $this->plugin->setConfigProperty('helloasso', $config);
        $this->plugin->save();
    }

    public function getCronConfig(): stdClass
    {
        return $this->plugin->getConfig('cron') ?? new stdClass;
    }

    public function saveCronConfig(stdClass $config): void
    {
        $this->plugin->setConfigProperty('cron', $config);
        $this->plugin->save();
    }

    public function asyncCron()
    {
        // Use of semaphore to avoid multiple request to trigger the script at the same time
        $semaphore = \sem_get(12345, 1);
        if (!$semaphore) {
            return;
        }

        if (\sem_acquire($semaphore)) { // wait for semaphore to be released
            $config = $this->getCronConfig();
            $lastSync = $config->last_sync ?? '1970-01-01';
            $today = date('Y-m-d');

            if ($today > $lastSync) { // DAILY
                $phpBin = PHP_BINDIR . '/php';

                exec($phpBin . ' ' . __DIR__ . '/../cron.php daily >> ' . __DIR__ . '/../cron.log 2>> ' . __DIR__ . '/../cron.log &');

                if (substr($today, 0, 7) > substr($lastSync, 0, 7)) { // MONTHLY
                    exec($phpBin . ' ' . __DIR__ . '/../cron.php monthly >> ' . __DIR__ . '/../cron.log 2>> ' . __DIR__ . '/../cron.log &');
                }

                $config->last_sync = $today;
                $this->saveCronConfig($config);
            }

            \sem_release($semaphore); // release semaphore
        }
    }
}
