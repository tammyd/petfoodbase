<?php

namespace PetFoodDB\Service;

use PetFoodDB\Traits\LoggerTrait;

/**
 * Class IdService
 *
 * @package PetFoodDB\Service
 */
class IdService
{
    use LoggerTrait;

    protected $enabled;

    public function __construct($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return string
     *
     */
    public function getInitAuthKey()
    {
        return $this->buildKey();
    }

    /**
     * @param $key
     *
     * @return bool
     *
     */
    public function isValidAuthKey($key)
    {
        if (!$this->enabled) {
            return true;
        }

        $newKey = $this->buildKey();

        return $key==$newKey;
    }

    /**
     * @return string
     *
     */
    protected function buildKey()
    {
        $seed = "Randomness!!";
        $sessionId = session_id();
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $agent = "CONSOLE_COMMAND";
        }
        $string = implode("!", [$seed, $sessionId, $agent]);

        $this->getLogger()->debug("Building key with $sessionId and $agent");

        return md5($string);
    }

    public function isEnabled() {
        return $this->enabled;
    }

}
