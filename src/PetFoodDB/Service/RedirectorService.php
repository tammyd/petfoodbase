<?php


namespace PetFoodDB\Service;


use Symfony\Component\Yaml\Parser;

class RedirectorService
{
    protected $ymlPath;
    protected $parser;
    protected $redirectData;

    public function __construct(Parser $yamlParser, $ymlPath)
    {

        $this->ymlPath = $ymlPath;
        $this->parser = $yamlParser;
        $this->redirectData = $this->parser->parse(file_get_contents($ymlPath));

    }

    public function getRedirectFor($path) {
        foreach ($this->redirectData['redirects'] as $code => $redirect) {
            if (array_key_exists($path, $redirect)) {
                return [$redirect[$path], $code];
            }
        }

        return null;
    }


}