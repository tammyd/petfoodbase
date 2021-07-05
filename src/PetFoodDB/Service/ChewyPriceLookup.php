<?php


namespace PetFoodDB\Service;


use PetFoodDB\Command\Tools\TooManySearchResultsException;
use PetFoodDB\Model\PetFood;
use PetFoodDB\Traits\ArrayTrait;
use PetFoodDB\Traits\StringHelperTrait;
use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

class ChewyPriceLookup implements PriceLookupInterface
{

    const CHEWY_BASE_URL = "https://www.chewy.com/";
    const USER_AGENT = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.96 Safari/537.36";

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
        if (isset($shopUrls['chewy']) && $shopUrls['chewy']) {
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
        $url = sprintf("%s%s%s", self::CHEWY_BASE_URL, $query, urlencode($searchTerm));
        return $url;

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
        if (!$rows) {
            return [];
        }

        $ppOunces = [];
        foreach ($rows as $row) {
            $ppOunces[] = $this->parseChewyPricePerOunce($row);
        }

        $ppOunces = array_filter($ppOunces);
        if ($ppOunces) {

            $low = round(min($ppOunces), 2);
            $high = round(max($ppOunces), 2);


            return [
                'low' => $low,
                'high' => $high,
                'avg' => round(array_sum($ppOunces) / count($ppOunces), 2),
                'name' => $rows[0]['name']
            ];
        } else {
            return [];
        }
    }

    public function getProducChewySearchTerm(PetFood $product) {
        return sprintf("%s cat food", $product->getDisplayName());
    }

    protected function getUrlCrawler($url)
    {
        $server = [
            "HTTP_USER_AGENT" => self::USER_AGENT
        ];
        $crawler = $this->client->request('GET', $url, [], [], $server);
        return $crawler;
    }

    protected function parseChewyPricePerOunce(array $row) {
        $ounces = $this->parseChewyWeight($row['name']);

        if ($ounces) {

            $priceOunce = floatval($row['price']) / $ounces;
            return $priceOunce;
        }
    }

    protected function parseChewyWeight($name) {
        $oz = [];
        $lb = [];
        $count = [];
        preg_match("/([\d\.]+)-oz/", $name, $oz);
        preg_match("/([\d\.]+)-lb/", $name, $lb);
        preg_match("/of (\d+)/", $name, $count);

        if (!$lb) {
            preg_match("/([\d\.]+)lb/", $name, $lb);
        }

        if (!$oz) {
            preg_match("/([\d\.]+)\soz/", $name, $oz);
        }
        if (!$oz) {
            preg_match("/([\d\.]+)oz/", $name, $oz);
        }

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
                    $urls[] = [
                        'url' => $item['canonicalURL'],
                        'price' => $item['price']
                        ];
                }

            }

        });


        $allProductVariationUrls = $urls;
        foreach ($allProductVariationUrls as $info) {
            $url = $info['url'];

            $crawler = $this->getUrlCrawler($url);
            $price = trim($crawler->filter('span.ga-eec__price')->text());

            $name = trim($crawler->filter('.ga-eec__name')->text());
            $ounces = $this->parseChewyWeight($name);

            $price = floatval(str_replace('$', '', $price));
            $priceOunce = null;

            if ($ounces && $price) {
                $priceOunce = floatval($price) / $ounces;
            }

            $result = [
                'name' => $name,
                'price' => $price, //$priceOunce,
                'url' => $url
            ];

            $rows[] = $result;

        }

        return $rows;

    }




    protected function fixJson($str) {
        $str = trim(preg_replace('/\s\s+/', ' ', $str));
        $str = preg_replace('/\s(\w+):\s/i', '"$1":', $str);
        $str = str_replace("'", '"', $str);
        $str = str_replace(", ]", " ]", $str);
        $str = str_replace("'", "\"", $str);
        $str = str_replace("`", "\"", $str);
        $str = substr($str, 0, -1);

        return $str;
    }

}