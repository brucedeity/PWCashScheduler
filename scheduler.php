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
require 'includes/configs.php';


$pw = new PW;
$worker = new Worker();

$worker->onWorkerStart = function () {
    global $configs;

    // Execute the function in the first second of every minute.
    new Crontab($configs['cron'], function(){
       echo $pw->sendCashToUsers();
    });
};


Worker::runAll();