<?php
/**
 * @author brucedeity
 * @create date 2022-11-19 18:15:40
 * @modify date 2022-11-27 13:48:40
 * @desc Handles configs.php file
 */

require 'configs.php';

class Config
{
    public function __construct()
    {
        global $configs;

        $this->configs = $configs;
    }

    /**
     * Gets all rewards
     * @return array
     */
    private function getRewards(int $key = NULL)
    {
        return is_null($key) ? $this->configs['rewards'] : $this->configs['rewards'][$key];
    }

    /**
     * Checks if exists a rewards for the given cultivation id
     * @param level2
     * @return boolean
     */
    public function checkLevel2(int $level2)
    {
        $rewards = $this->getRewards($level2);

        if (!$rewards) return false;

        if ($rewards['cash'] <= 0) return false;

        return true;
    }

    /**
     * Gets cash for the given level2
     * @param level2
     * @return array
     */
    public function getLevel2Reward(int $level2)
    {
        // Returns the method if level2 is invalid
        if (!$this->checkLevel2($level2)) return;

        return $this->getRewards($level2);
    }
}