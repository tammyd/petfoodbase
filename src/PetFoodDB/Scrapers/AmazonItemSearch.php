<?php

namespace PetFoodDB\Scrapers;

use ApaiIO\Operations\Search;

class AmazonItemSearch extends Search
{
    /**
     * Sets the amazon $brand
     *
     * @param string $brand
     *
     * @return \ApaiIO\Operations\Search
     */
    public function setBrand($brand)
    {
        $this->parameter['Brand'] = $brand;

        return $this;
    }

}
