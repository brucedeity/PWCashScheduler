<?php
/**
 * @author brucedeity
 * @create date 2022-11-20 16:30:35
 * @modify date 2022-11-20 16:30:35
 */

use Workerman\Worker;
use Workerman\Crontab\Crontab;

require __DIR__ . '/vendor/autoload.php';

require 'includes/pw.php';

$worker = new Worker();

$worker->onWorkerStart = function () {
    global $configs;

    // Execute this function accordin to cron configs key.
    new Crontab($configs['cron'], function(){
       echo (new PW)->sendCashToUsers()."\n";
    });
};


Worker::runAll();