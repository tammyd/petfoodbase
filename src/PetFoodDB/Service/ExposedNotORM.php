<?php

namespace PetFoodDB\Service;

class ExposedNotORM extends \NotORM
{
    public function getConnection()
    {
        return $this->connection;
    }

}
