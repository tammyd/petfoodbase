<?php

namespace PetFoodDB\Scrapers\Traits;

use PetFoodDB\Traits\StringHelperTrait;
use Symfony\Component\DomCrawler\Crawler;

trait StandardTableNutritionParserTrait
{
    use StringHelperTrait;

    protected function getCellText(Crawler $cellNodes)
    {
        $cellText = [];
        foreach ($cellNodes as $i=>$cell) {
            $cellText[$i] = $cell->nodeValue;
        }

        return $cellText;
    }

    protected function parseNutritionFromCells(Crawler $cellNodes, $skipRows = 0)
    {
        $data = [];
        $data['ash'] = 0;

        $cellText = $this->getCellText($cellNodes);

        $offset = $skipRows*2;

        $skipIf = $this->getSkippedCellText();
        foreach ($cellText as $i=>$text) {

            $previous = ($i > 0) ? $cellText[$i-1] : "";

            if ($this->containsAny($previous, $skipIf, false)) {
                continue;
            }
            
            switch ($i) {
                case 0 + $offset:
                case 2 + $offset:
                case 4 + $offset:
                case 6 + $offset:
                    break; //do nothing
                case 1 + $offset: $data['protein'] = floatval(explode(" ", $text)[0]); break;
                case 3 + $offset: $data['fat'] = floatval(explode(" ", $text)[0]); break;
                case 5 + $offset: $data['fibre'] = floatval(explode(" ", $text)[0]); break;
                case 7 + $offset: $data['moisture'] = floatval(explode(" ", $text)[0]); break;
                case 9 + $offset: $data['ash'] = floatval(explode(" ", $text)[0]); break;
                default:
                    break;
            }
        }

        return $data;
    }
    
    

    protected function getSkippedCellText()
    {
        return ['/kg', 'ppm', 'carb','kcal', '/lb'];
    }
}
