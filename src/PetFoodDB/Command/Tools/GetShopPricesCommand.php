<?php


namespace PetFoodDB\Command\Tools;

use PetFoodDB\Traits\StringHelperTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;


use PetFoodDB\Traits\ArrayTrait;

class GetShopPricesCommand extends SchemaParseCommand
{
    
    use ArrayTrait, StringHelperTrait;

    protected function configure()
    {
        $this
            ->setDescription("Get prices for product from schema.org")
            ->setName('prices:parse')
            ->addArgument('id', InputArgument::REQUIRED, 'Product Id');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        TableCommand::execute($input, $output);

        $id = $input->getArgument('id');
        $shopService = $this->container->get('shop.service');
        $catFoodService = $this->container->get('catfood');
        $urls = $shopService->getAll($id);
        $catFood = $catFoodService->getById($id);

        $chewyUrl = $urls['chewy'];
        $rows = $this->parseChewyUrlForPriceData($chewyUrl);


        foreach ($rows as $row) {
            $ounces = $this->getChewyWeight($row['name']);
            $priceOunce = floatval($row['price']) / $ounces;

            $output->writeln(sprintf("%s is \$%0.02f per oz.", $catFood->getDisplayName(), $priceOunce));
        }

    }

    protected function getPricePerOunce(array $row) {
        $ounces = $this->getChewyWeight($row['name']);
        $priceOunce = floatval($row['price']) / $ounces;
        return $priceOunce;
    }

    protected function getChewyWeight($name) {
        $oz = [];
        $lb = [];
        $count = [];
        preg_match("/([\d\.]+)-oz/", $name, $oz);
        preg_match("/([\d\.]+)-lb/", $name, $lb);
        preg_match("/of (\d+)/", $name, $count);


        $totalOunces = isset($oz[1]) ? $oz[1] : 0;
        $totalOunces += isset($lb[1]) ? $lb[1]*16 : 0;
        $totalOunces *= isset($count[1]) ? $count[1] : 1;

        return $totalOunces;
    }

    protected function parseChewyUrlForPriceData($url) {
        $this->client = new Client();
        $crawler = $this->getUrlCrawler($url);
        $urls = [];
        $rows = [];
        $scripts = $crawler->filter('script')->each(function($node) use (&$urls) {
            $text = $node->text();
            $check = "var itemData = {";
            if ($this->contains($text, "canonicalURL")) {

                //pull out json and fix it... sheesh
                $pos = strpos($text, "var itemData = {");
                $str = substr($text, $pos + strlen($check) - 1);
                $str = $this->fixJson($str);
                $dataArr = json_decode($str, true);

                foreach ($dataArr as $item) {
                    $urls[] = $item['canonicalURL'];
                }

            }

        });


        foreach ($urls as $url) {
            $data = $this->getPriceData($url);
            $rows = array_merge($rows, $data);
        }

        return $rows;
    }

    protected function parsePriceDataRows(array $rows) {
        $ppOunces = [];
        foreach ($rows as $row) {
            $ppOunces[] = $this->getPricePerOunce($row);
        }

        return [
            'low' => round(min($ppOunces),2),
            'high' => round(max($ppOunces),2),
            'avg' => round(array_sum($ppOunces) / count($ppOunces),2)
        ];
    }

    protected function fixJson($str) {
        $str = trim(preg_replace('/\s\s+/', ' ', $str));
        $str = preg_replace('/\s(\w+):\s/i', '"$1":', $str);
        $str = str_replace("'", '"', $str);
        $str = str_replace(", ]", " ]", $str);
        $str = substr($str, 0, -1);
        return $str;
    }

    protected function getUrlCrawler($url)
    {
        $crawler = $this->client->request('GET', $url);
        return $crawler;
    }



}
