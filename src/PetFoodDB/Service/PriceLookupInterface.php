<?php


namespace PetFoodDB\Service;


use PetFoodDB\Model\PetFood;

interface PriceLookupInterface
{
    public function lookupPrice(PetFood $product);
}
