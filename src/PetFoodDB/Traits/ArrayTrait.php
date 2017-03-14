<?php


namespace PetFoodDB\Traits;


trait ArrayTrait
{

    function getArrayValue($array, $key, $default = null) {
        if (isset($array[$key])) {
            return $array[$key];
        }

        return $default;
    }

}
