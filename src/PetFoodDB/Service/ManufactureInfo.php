<?php


namespace PetFoodDB\Service;

use Symfony\Component\Yaml\Parser;


class ManufactureInfo
{
    protected $ymlPath;
    protected $parser;
    protected $brandData;

    public function __construct(Parser $yamlParser, $ymlPath)
    {
        $this->ymlPath = $ymlPath;
        $this->parser = $yamlParser;
        $this->brandData = $this->parser->parse(file_get_contents($ymlPath));
    }


    public function getBrandInfo($brand)
    {
        $brand = strtolower($brand);
        
        if (isset($this->brandData[$brand])) {
            return $this->brandData[$brand];
        }

        return null;

    }


}
