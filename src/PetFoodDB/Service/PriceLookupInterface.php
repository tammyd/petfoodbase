<?php


namespace PetFoodDB\Service;


use PetFoodDB\Model\CatFood;

interface PriceLookupInterface
{
    public function lookupPrice(CatFood $product);
}
