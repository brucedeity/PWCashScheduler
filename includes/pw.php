<?php
/**
 * Handles pw database
 * Has functions to handle all pw database and tables
 * 
 * @author brucedeity
 * @since 2022-11-19 14:34:44
 */

require __DIR__.'/../vendor/autoload.php';

require __DIR__.'/../api/api.php';

require 'configs.php';
require 'confHandler.php';
require 'logger.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;

class PW
{
    public function __construct()
    {
        $env = new Dotenv;
        $env->load(__DIR__.'/.env');

        $this->db = DriverManager::getConnection(['dbname' => $_ENV['DB_NAME'],'user' => $_ENV['DB_USER'],'password' => $_ENV['DB_PASS'],'host' => $_ENV['DB_HOST'],'driver' => $_ENV['DB_DRIVER'],]);
    
        $this->level2 = [];
    }

    /**
     * gets all user in db
     * @return array
     */
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

    /**
     * Sends cash to the user
     * @param ID
     * @param cash
     */
    public function AddCash($ID, $cash)
    {
        $sql = 'call usecash (?, ?, ? ,?, ?, ?, ?, @error)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $ID);
        $stmt->bindValue(2, 1);
        $stmt->bindValue(3, 0);
        $stmt->bindValue(4, 1);
        $stmt->bindValue(5, 0);
        $stmt->bindValue(6, $cash * 100);
        $stmt->bindValue(7, 1);
        $stmt->executeQuery();
    }

    /**
     * Call a given method of the API
     * @param method
     * @param params array
     * 
     * @return method
     */
    public function callApi($method, array $params = [])
    {
        return call_user_func_array([new API, $method], $params);
    }

    /**
     * Loops through all accounts in db to get it's valid roles that may receive reward(s)
     * @return array
     */
    public function checkAccounts()
    {
        $checked = [];
        foreach($this->getUsers() as $account) {

            // Current account roles that can receive a reward
            $checkdRoles = [];

            $roles = $this->callApi('getRoles', ['user' => $account['ID']]);

            $confHandler = new Config;

            // Skips this iteration because this account has no roles
            if (!array_key_exists('roles', $roles)) continue;

            foreach($roles['roles'] as $role){

                // Gets full role
                $fullRole = $this->callApi('getRole', ['role' => $role['id']]);

                // Skip this iteration because fullRole is not valid
                if (!is_array($fullRole)) continue;

                // Extra checks, may be not needed so commenting it
                // if (!array_key_exists('base', $fullRole)) continue;

                // if (!array_key_exists('status', $fullRole)) continue;

                $level2 = $fullRole['status']['level2'];

                // Checks if exists a reward to the given level2
                if (!$confHandler->checkLevel2($level2)) continue;

                // Stores all usefull data into roleData array
                $roleData = [
                    'accountID' => $account['ID'],
                    'roleId' => $fullRole['base']['id'],
                    'roleName' => $fullRole['base']['name'],
                    'level2' => $level2,
                    'reward' => $confHandler->getLevel2Reward($level2)
                ];

                // Pushs roleData to checkedRoles array
                array_push($checkdRoles, $roleData);
            }

            // Puts the checked roles into the account ID key in way to return valid roles to all accounts
            array_push($checked, $checkdRoles);
        }

        // Returns an array of all accounts ID and it's valid roles
        return $checked;
    }

    /**
     * Gets level 2 name
     * @param level2 int
     * @return string
     */
    public function getLevel2Name(int $level2) : string
    {
        $names = [
            0 => 'Leal (Inicial)',
            1 => 'Astuto (9)',
            2 => 'Harmonioso (19)',
            3 => 'Lúcido (29)',
            4 => 'Enigmático (39)',
            5 => 'Ameaçador (49)',
            6 => 'Sinistro (59)',
            7 => 'Nirvana (69)',
            8 => 'Mahayana (79)',
            20 => 'Nobre (God 1)',
            21 => 'Iluminado (God 2)',
            22 => 'Imortal (God 3)',
            30 => 'Diabólico (Evil 1)',
            31 => 'Infernal (Evil 2)',
            32 => 'Demoníaco (Evil 3)'
        ];

        return array_key_exists($level2, $names) ? $names[$level2] : 'Unknow';
    }

    public function getRoleWithHighestLevel2(array $roles)
    {
        $parsedRole = [];

        // Level2 0 is a thing.. so start value must be -1
        $highestLevel2 = -1;

        foreach ($roles as $role) {
            if ($role['level2'] > $highestLevel2) {
                $highestLevel2 = $role['level2'];

                $parsedRole = $role;
            }
        }

        return $parsedRole;
    }

    /**
     * Loops through all valid accounts (that has at least one character and it's level2 e rewardable) and stores
     * it in an array caled maxRewards in this way: ['1024' => [1, 2, 3, 4, 5]]
     * 
     * @return array
     */
    public function getMaxRewards()
    {
        $checkedAccounts = $this->checkAccounts();

        
        $maxRewards = [];
        foreach ($checkedAccounts as $key => $value) {
            $parsedRole = $this->getRoleWithHighestLevel2($checkedAccounts[$key]);
            
            if (!is_array($parsedRole)) continue;

            array_push($maxRewards, $parsedRole);
        }

        return $maxRewards;
    }

    public function sendCash()
    {
        $logger = new Logger('../logs/logs.txt');

        foreach ($this->getMaxRewards() as $account) {

            $this->AddCash($account['accountID'], $account['reward']['cash']);

            $logger->putLog('A conta '.$account['accountID'].' recebeu '.$account['reward']['cash']. ' em cash, porque o personagem '. $account['roleName']. ' tem o cultivo: '.$this->getLevel2Name($account['level2']));
        }
    }
}

print_r(json_encode((new PW)->sendCash()));