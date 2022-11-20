<?php
/**
 * @author brucedeity
 * @create date 2022-11-19 18:15:40
 * @modify date 2022-11-19 18:15:40
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
    private function getRewards()
    {
        return $this->configs['rewards'];
    }

    /**
     * Checks if exists a rewards for the given cultivation id
     * @param level2
     * @return boolean
     */
    public function checkLevel2(int $level2)
    {
        $rewards = $this->getRewards();

        if (!array_key_exists($level2, $rewards)) return false;

        if ($rewards[$level2]['cash'] < 0) return false;

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

        return $this->getRewards()[$level2];
    }
}
