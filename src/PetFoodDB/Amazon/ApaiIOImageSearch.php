<?php

namespace PetFoodDB\Amazon;

use ApaiIO\Operations\Search;

class ApaiIOImageSearch extends Search
{
    public function __construct()
    {
        $this->parameter['ResponseGroup'] = 'Images';
        $this->parameter['IdType'] = 'ASIN';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ItemLookup';
    }

    /**
     * Sets the amazon $asin
     *
     * @param string $asin
     *
     * @return \ApaiIO\Operations\Search
     */
    public function setASIN($asin)
    {
        $this->parameter['ItemId'] = $asin;

        return $this;
    }

}
