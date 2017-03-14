<?php

namespace PetFoodDB\Traits;

use Monolog\Logger;
use Psr\Log\NullLogger;

trait LoggerTrait
{
    protected $logger;

    /**
     * @param Logger $logger
     *
     * @return $this
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        if (is_null($this->logger)) {
            return new NullLogger();
        }

        return $this->logger;
    }

}
