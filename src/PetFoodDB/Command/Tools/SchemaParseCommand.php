<?php


namespace PetFoodDB\Command\Tools;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Jkphl\Micrometa;

use PetFoodDB\Traits\ArrayTrait;

class SchemaParseCommand extends TableCommand
{
    
    use ArrayTrait;

    protected function configure()
    {
        $this
            ->setDescription("parse schema.org data")
            ->setName('schema:parse')
            ->addArgument('url', InputArgument::REQUIRED, 'Url to parse');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $url = $input->getArgument('url');
        $rows = $this->getPriceData($url);


        $this->outputCSV(array_values($rows));


    }

    protected function getPriceData($url) {
        $micrometaParser = new \Jkphl\Micrometa($url);
        $micrometaObjectData = $micrometaParser->toObject();

        $items = $micrometaObjectData->items;

        $rows = [];

        foreach ($items as $item) {
            $properties = $item->properties;
            $offers = $this->getArrayValue($properties, 'offers', []);
            $nameArr = $this->getArrayValue($properties, 'name', []);
            $name = isset($nameArr[0]) ? $nameArr[0] : "";


            if ($offers || $name) {
                $prices = [];
                foreach ($offers as $offer) {
                    $price = $this->getArrayValue($offer->properties, 'price', [null])[0];
                    $prices[] = $price;
                }
                $rows[] = [
                    'url' => $url,
                    'name' => $name,
                    'price' => implode(", ", $prices)
                ];
            }

        }

        return $rows;
    }

}
