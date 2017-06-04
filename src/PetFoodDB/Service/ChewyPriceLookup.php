<?php


namespace PetFoodDB\Service;


use PetFoodDB\Command\Tools\TooManySearchResultsException;
use PetFoodDB\Model\PetFood;
use PetFoodDB\Traits\ArrayTrait;
use PetFoodDB\Traits\StringHelperTrait;
use Goutte\Client;

class ChewyPriceLookup implements PriceLookupInterface
{

    const CHEWY_BASE_URL = "https://www.chewy.com/";

    protected $client;
    protected $shopService;

    use ArrayTrait, StringHelperTrait;


    public function __construct(Client $client, ShopService $shopService)
    {
        $this->client = $client;
        $this->shopService = $shopService;
    }

    public function lookupPrice(PetFood $product, $skipChewySearch = false) {

        //if chewy url exists in product, use that.
        $shopUrls = $this->shopService->getAll($product->getId());
        $data = [];
        if (isset($shopUrls['chewy'])) {
            $url = $shopUrls['chewy'];
            $rawResults = $this->parseAllChewyPriceDataFromBaseProductUrl($url);
            $data = $this->parseRawChewyData($rawResults);
            if ($data) {
                $data['productUrl'] = $url;
                $data['url'] = $url;
            }
        } else if (!$skipChewySearch) {
            //otherwise search
            $searchTerm = $this->getProducChewySearchTerm($product);
            try {
                $data = $this->searchChewyPrice($searchTerm);
            } catch (TooManySearchResultsException $e) {
                $searchUrl = $this->getChewySearchUrl($searchTerm);
                dump("Too Many Search Results for #" . $product->getId() . ": " . $e->getMessage());
                dump($searchUrl);
                $data = [];
            }
            if ($data) {
                $data['searchUrl'] = $this->getChewySearchUrl($searchTerm);
                $data['search'] = $searchTerm;
                $data['url'] = $data['productUrl'];


            }
        }

        if ($data) {
            $data['date'] = date(DATE_RFC2822);
            $data['id'] = $product->getId();
        }



        return $data;
    }

    public function getChewySearchUrlForProduct(PetFood $product) {
        $search = $this->getProducChewySearchTerm($product);
        return $this->getChewySearchUrl($search);
    }



    public function getChewySearchUrl($searchTerm) {

        $query = "s?query=";
        return sprintf("%s%s%s", self::CHEWY_BASE_URL, $query, urlencode($searchTerm));

    }

    protected function searchChewyPrice($searchTerm) {

        $searchUrl = $this->getChewySearchUrl($searchTerm);

        $crawler = $this->getUrlCrawler($searchUrl);

        //do we have any results
        $noResultsTexts = ['Sorry, your search', 'did not match any products.'];
        $html = $crawler->html();
        if ($this->containsAny($html, $noResultsTexts)) {
            return [];
        }


        $resultProducts = $crawler->filter('section.results-products');
        if (!$resultProducts->count()) {
            return [];
        }

        $product = $resultProducts->eq(0);

        $productCount = $product->filter('article.product-holder')->count();

        if ($productCount > 1) {
            //too many search results to know for sure we have the correct results
            throw new TooManySearchResultsException($searchTerm);
            return [];
        }


        $link = $product->filter('a');
        $uri = self::CHEWY_BASE_URL.$link->eq(0)->attr('href');


        $rawResults = $this->parseAllChewyPriceDataFromBaseProductUrl($uri);

        $data = $this->parseRawChewyData($rawResults);
        $data['productUrl'] = $uri;

        return $data;

    }


    protected function parseRawChewyData(array $rows) {
        $ppOunces = [];
        foreach ($rows as $row) {
            $ppOunces[] = $this->parseChewyPricePerOunce($row);
        }

        return [
            'low' => round(min($ppOunces),2),
            'high' => round(max($ppOunces),2),
            'avg' => round(array_sum($ppOunces) / count($ppOunces),2),
            'name' => $rows[0]['name']
        ];
    }

    public function getProducChewySearchTerm(PetFood $product) {
        return sprintf("%s cat food", $product->getDisplayName());
    }

    protected function getUrlCrawler($url)
    {
        $crawler = $this->client->request('GET', $url);
        return $crawler;
    }

    protected function parseChewyPricePerOunce(array $row) {
        $ounces = $this->parseChewyWeight($row['name']);

        $priceOunce = floatval($row['price']) / $ounces;
        return $priceOunce;
    }

    protected function parseChewyWeight($name) {
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

    protected function parseAllChewyPriceDataFromBaseProductUrl($url) {
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
            $data = $this->parseSingleChewyPriceFromUrl($url);
            $rows = array_merge($rows, $data);
        }

        return $rows;
    }

    protected function parseSingleChewyPriceFromUrl($url) {
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

    protected function fixJson($str) {
        $str = trim(preg_replace('/\s\s+/', ' ', $str));
        $str = preg_replace('/\s(\w+):\s/i', '"$1":', $str);
        $str = str_replace("'", '"', $str);
        $str = str_replace(", ]", " ]", $str);
        $str = substr($str, 0, -1);
        return $str;
    }

}