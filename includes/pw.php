<?php
/**
 * Handles pw database
 * Has functions to handle all pw database and tables
 * 
 * @author brucedeity
 * @since 2022-11-19 14:34:44
 */

require __DIR__.'/vendor/autoload.php';

require '../api/api.php';
require '../includes/confHandler.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;

class PW
{
    public function __construct()
    {
        $env = new Dotenv;
        $env->load(__DIR__.'/.env');

        $this->db = DriverManager::getConnection(['dbname' => $_ENV['DB_NAME'],'user' => $_ENV['DB_USER'],'password' => $_ENV['DB_PASS'],'host' => $_ENV['DB_HOST'],'driver' => $_ENV['DB_DRIVER'],]);
    }

    public function getUsers()
    {
        $sql = "SELECT * FROM users";

        $stmt = $this->db->query($sql);

        $users = [];

        while (($row = $stmt->fetchAssociative()) !== false) {
            $users[] = $row;
        }

        return $users;
    }

    public function AddCash($ID, $cash)
    {
        $this->con->query('call usecash (?, ?, ? ,?, ?, ?, ?, @error)', [$ID, 1, 0, 1, 0, $cash * 100, 1]);
    }

    public function callApi($method, array $params = [])
    {
        return call_user_func_array([new API, $method], $params);
    }

    public function checkAccounts()
    {
        $checked = [];
        foreach($this->getUsers() as $account) {

            $checkdRoles = [];

            $roles = $this->callApi('getRoles', ['user' => $account['ID']]);

            $confHandler = new Config;

            foreach($roles['roles'] as $role){

                // Gets full role
                $fullRole = $this->callApi('getRole', ['role' => $role['id']]);

                // Checks if exists a reward to the given level2
                if (!$confHandler->checkLevel2($role['base']['level2'])) continue;

                // Stores the valid role
                $checkedRoles[] = $fullRole;
            }
        }
    }
}

print_r(json_encode((new PW)->checkAccounts()));
