<?php


namespace PetFoodDB\Service;


use PetFoodDB\Model\PetFood;
use Khill\Lavacharts\Lavacharts;

class ChartDataService
{
    protected $lavaCharts;

    /**
     * ChartDataService constructor.
     */
    public function __construct(Lavacharts $lavaCharts)
    {
        $this->lavaCharts = $lavaCharts;
    }

    public function getCalorieChart(PetFood $product) {
        $calories = $product->getCaloriesPer100g();

        $title = "CalorieBreakdown" . $product->getId();
        $data = $this->lavaCharts->DataTable();
        $data->addStringColumn('Col1')
            ->addNumberColumn('Col2');
        $data->addRows([
            ['Calories From Carbs', $calories['carbohydrates']],
            ['Calories From Fat', $calories['fat']],
            ['Calories From Protein', $calories['protein']]
        ]);

        $this->lavaCharts->PieChart($title, $data, [
            'chartArea' => [
                'left' => 0,
                'top' =>8,
                'width' => '100%',
                'height' => '92%',

            ],
            'legend' => [
                'position' => 'right'
            ],
            'slices' => [
                ['color'=>'#A94441', 'textStyle' => ['fontSize' => '14'] ],
                ['color'=>'#8a6d3b', 'textStyle' => ['fontSize' => '14'] ],
                ['color'=>'#3e3354', 'textStyle' => ['fontSize' => '14'] ],
            ],
        ]);


        return $title;



    }

}
