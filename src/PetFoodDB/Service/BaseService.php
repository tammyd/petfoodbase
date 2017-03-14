<?php

namespace PetFoodDB\Service;

use PetFoodDB\Traits\LoggerTrait;

abstract class BaseService
{
    use LoggerTrait;

    protected $db;

    public function __construct(\NotORM $db)
    {
        $this->db = $db;
    }

    /**
     * @return \NotORM
     */
    public function getDb()
    {
        return $this->db;
    }

    public function getPDO() {
        return $this->db->getConnection();
    }

}
