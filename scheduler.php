<?php
/**
 * @author brucedeity
 * @create date 2022-11-19 15:20:03
 * @modify date 2022-11-19 15:20:03
 * @desc handles schedule
 */

require __DIR__.'/vendor/autoload.php';
require 'includes/pw.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;

class Scheduler
{
    public function __construct()
    {
        $this->pw = new PW;
    }
}