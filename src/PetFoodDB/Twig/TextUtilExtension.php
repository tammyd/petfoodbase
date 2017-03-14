<?php


namespace PetFoodDB\Twig;


class TextUtilExtension extends \Twig_Extension
{


    public function getName()
    {
        return "textUtilExtension";
    }


    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('commaList', [$this, 'commaList']),
            new \Twig_SimpleFilter('isOrAre', [$this, 'isOrAre']),
        ];
    }


    public function commaList($list, $separator="and") {
        if (!$list || !is_array($list)) {
            return "";
        }

        //remove any empties
        $list = array_filter($list);

        if (count($list) <= 1) {
            return array_pop($list);
        }
        $subList = array_slice($list, 0, -1);
        return join(", ", $subList) . " $separator " . end($list);
    }

    public function isOrAre(array $list) {
        return count($list) > 1 ? "are" : "is";
    }
    




}
