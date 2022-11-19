<?php
/**
 * Handles pw database
 * Has functions to handle all pw database and tables
 * 
 * @author brucedeity
 * @since 2022-11-19 14:34:44
 */

require __DIR__.'/vendor/autoload.php';

require 'api/api.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;

class PW
{
    public function __construct()
    {
        $env = new Dotenv;
        $env->load(__DIR__.'/.env');

        $this->con = DriverManager::getConnection(['dbname' => $_ENV['DB_NAME'],'user' => $_ENV['DB_USER'],'password' => $_ENV['DB_PASS'],'host' => $_ENV['DB_HOST'],'driver' => $_ENV['DB_DRIVER'],]);
    }

    public function getUsers()
    {
        return $this->con->query('SELECT * FROM users')->fetch();
    }

    public function callApi($method, array $params = [])
    {
        return call_user_func_array([new API, $method], $params);
    }
}
