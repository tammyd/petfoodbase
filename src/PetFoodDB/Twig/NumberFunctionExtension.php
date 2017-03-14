<?php


namespace PetFoodDB\Twig;


class NumberFunctionExtension extends \Twig_Extension
{


    public function getName()
    {
       return "numberFunctions";
    }


    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('round5', [$this, 'roundUpToAny']),
            new \Twig_SimpleFilter('lessOrMore', [$this, 'lessOrMore']),
            new \Twig_SimpleFilter('is_numeric', [$this, 'isNumeric']),
        ];
    }


    public function roundUpToAny($value,  $roundTo = 5) {
        
        return (round($value)%$roundTo === 0) ? round($value) : round(($value+$roundTo/2)/$roundTo)*$roundTo;
    }

    public function lessOrMore($a) {

        return $a < 0 ? "less" : "more";
    }

    public function isNumeric($x) {
        return is_numeric($x);
    }
    


}
