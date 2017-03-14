<?php

namespace PetFoodDB\Scrapers;

use PetFoodDB\Amazon\Lookup;
use PetFoodDB\Model\CatFood;
use PetFoodDB\Service\YmlLookup;
use PetFoodDB\Traits\LoggerTrait;
use PetFoodDB\Traits\StringHelperTrait;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\RequestException;

abstract class BaseCatFoodScraper
{
    use StringHelperTrait,
        LoggerTrait;

    /**
     * @var \Goutte\Client scraper client
     */
    protected $client;
    /**
     * @var int Sleep time between requests in microseconds
     */
    protected $sleep = 1000000; //default sleep between calls is 1s
    /**
     * @var \PetFoodDB\Amazon\Lookup Lookup for amazon info
     */
    protected $amazon;
    protected $dataUrls = [];
    protected $nonDataUrls = [];
    protected $errorUrls = [];
    protected $manualLookup;
    protected $currentUrl;

    /**
     * @param Lookup $amazon
     */
    public function __construct(Lookup $amazon, YmlLookup $manualLookup)
    {
        $this->amazon = $amazon;
        $this->client = new Client();
        $this->manualLookup = $manualLookup;
    }

    abstract public function getSitemapUrl();

    public function isPossibleProductUrl($url)
    {
        return true;
    }

    protected function parseNutrition(Crawler $crawler)
    {
        return [];
    }

    protected function parseIngredients(Crawler $crawler)
    {
        return [];
    }

    protected function parseProduct(Crawler $crawler)
    {
        return $this->lookupManualData(['brand', 'product', 'flavor']);
    }

    protected function getNutrition(Crawler $crawler)
    {
        $data = $this->parseNutrition($crawler);
        $manual = $this->lookupManualData(['protein', 'fat', 'moisture', 'fibre', 'ash']);

        if (is_array($data)) {
            $data = array_merge($data, $manual);
        } else {
            return [];
        }

        return $data;
    }

    protected function getIngredients(Crawler $crawler)
    {
        $data = $this->parseIngredients($crawler);
        $manual = $this->lookupManualData(['ingredients']);

        if (is_array($data)) {
            return array_merge($data, $manual);
        } else {
            return [];
        }
    }

    protected function getProduct(Crawler $crawler)
    {
        $data = $this->parseProduct($crawler);
        $manual = $this->lookupManualData(['brand', 'product', 'flavor']);

        if (is_array($data)) {
            return array_merge($data, $manual);
        } else {
            return [];
        }
    }

    /**
     * Parse the sitemap for an array of urls. Only parses top level urls.
     *
     * @return array
     */
    public function getSiteMapUrls()
    {

        $guzzleClient = $this->client->getClient();

        $response = $guzzleClient->get($this->getSitemapUrl());


        $body = $response->getBody();
        $xml = simplexml_load_string($body);

        return $this->parseSitemapXmlIntoUrls($xml);
    }

    protected function parseSitemapXmlIntoUrls(\SimpleXMLElement $xml)
    {
        $urls = [];

        foreach ($xml->url as $url) {
            $urls[] = (string)$url->loc;
        }

        sort($urls);
        $urls = array_unique($urls);

        return $urls;


    }

    public function recordNonProductUrl($url)
    {
        $this->nonDataUrls[] = $url;
    }

    /**
     * Scrapes all urls found in sitemap and returns CatFood objects
     *
     * @return CatFood[]
     */
    public function scrape($urls = [])
    {

        $catFoodItems = [];

        if (!count($urls)) {
            $urls = $this->getSiteMapUrls();
        }


        foreach ($urls as $url) {
            $catFood = $this->scrapeUrl($url);
            if ($catFood) {
                $catFoodItems[] = $catFood;
            }
            usleep($this->sleep);
        }

        return $catFoodItems;
    }

    public function getUrlsScraped()
    {
        return [$this->dataUrls, $this->nonDataUrls, $this->errorUrls];
    }

    public function getKeywordsForFood(CatFood $catfood)
    {
        $replace = ['™'];

        $name = $catfood->getDisplayName();
        $name = str_replace($replace, "", $name);
        $name = $this->cleanText($name);

        return $name;
    }

    /**
     * @param string $url Scrapes a single url and returns a catfood object
     *
     * @return CatFood|null
     */
    public function scrapeUrl($url)
    {
        $data = null;
        $catFood = null;

        if ($this->isPossibleProductUrl($url)) {
            $data = $this->parse($url, []);
        }
        if (!empty($data)) {
            $catFood = new CatFood($data);
            $amazon = $this->lookupAmazon($this->getKeywordsForFood($catFood));
            $data = array_merge($data, $amazon);
            $catFood = new CatFood($data);
            $this->dataUrls[] = $url;
        } else {
            $this->nonDataUrls[] = $url;
        }

        return $catFood;
    }

    /**
     * @param int $sleep # of ms to sleep for between url requests
     *
     * @return $this
     */
    public function setSleep($sleep)
    {
        $this->sleep = (int)$sleep * 1000;

        return $this;
    }

    protected function handleUrlError($url, \Exception $error, $prefix = "Error")
    {
        $msg = sprintf("%s %s (%s)", get_class($error), $prefix, $error->getMessage());
        $this->getLogger()->error($msg);
        $this->errorUrls[] = $url;
    }

    public function parseProductData($url)
    {

        $productData = [];

        try {
            $crawler = $this->getUrlCrawler($url);
        } catch (RequestException $e) {
            $this->handleUrlError($url, $e, "Guzzle Error");

            return $productData;
        } catch (\RuntimeException $e) {
            $this->handleUrlError($url, $e, "Runtime Error");

            return $productData;
        }

        $productData = $this->getProduct($crawler);

        return $productData;


    }

    protected function getUrlCrawler($url)
    {

        $this->updateGuzzleClient();
        $crawler = $this->client->request('GET', $url);
        return $crawler;

    }

    protected function prepUrl($url)
    {
        return $url;
    }

    /**
     * Parse a url
     *
     * @param string $url Url to parse
     *
     * @return array|null
     */
    public function parse($url)
    {
        $this->currentUrl = $url;
        $this->getLogger()->debug("Opening $url");
        $data = [];

        try {
            $crawler = $this->getUrlCrawler($url);
        } catch (RequestException $e) {
            $this->handleUrlError($url, $e, "Guzzle Error");

            return $data;
        } catch (\RuntimeException $e) {
            $this->handleUrlError($url, $e, "Runtime Error");

            return $data;
        }

        $productData = $this->getProduct($crawler);
        if (!$productData) {
            return $data; //need at least a product defined
        }
        $nutrition = $this->getNutrition($crawler);
        $ingredients = $this->getIngredients($crawler);
        if ($nutrition && $ingredients) {
            $data['source'] = $this->prepUrl($url);
            $data['parserClass'] = get_called_class();
            $data = $this->normalizekNutritionData($data);

            $data = array_merge($data, $nutrition, $ingredients, $productData);
        }

        return $data;
    }

    protected function updateGuzzleClient()
    {
        //do nothing
    }

    protected function normalizekNutritionData(array $data)
    {
        $requireZero = ['ash', 'fibre', 'moisture', 'fat', 'protein'];
        foreach ($requireZero as $key) {
            if (!isset($data[$key])) {
                $data[$key] = 0;
            }
        }

        return $data;
    }

    /**
     * Lookup amazon information given a description of the product
     *
     * @param string $displayName Product description
     *
     * @return array Keys include 'asin', 'imageUrl'
     */
    protected function lookupAmazon($displayName)
    {
        $data = $this->lookupManualData(['asin', 'imageUrl']);
        $data = $this->useExistingOr($data, 'asin', $this->amazon->lookupAsinByKeywords($displayName));
        if ($data['asin']) {
            $data = $this->useExistingOr($data, 'imageUrl', $this->amazon->lookupImageUrlByAsin($data['asin']));
        }
        $data = $this->useExistingOr($data, 'imageUrl', "");

        return $data;

    }

    protected function useExistingOr($data, $key, $value)
    {
        $data[$key] = array_key_exists($key, $data) ? $data[$key] : $value;

        return $data;

    }

    protected function lookupManualData(array $fields)
    {
        $data = [];
        foreach ($fields as $field) {
            $value = $this->manualLookup->lookup($this->currentUrl, $field, false);
            if ($value !== false) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

}
