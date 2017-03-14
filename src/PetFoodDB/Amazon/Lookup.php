<?php

namespace PetFoodDB\Amazon;

use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Search;
use PetFoodDB\Traits\LoggerTrait;

class Lookup
{
    use LoggerTrait;

    protected $config;
    protected $api;

    public function __construct($accessKey, $secretKey, $associateTag)
    {

        $this->config = new GenericConfiguration();

        $client = new \GuzzleHttp\Client();
        $request = new \ApaiIO\Request\GuzzleRequest($client);

        $this->config
            ->setCountry('com')
            ->setAccessKey($accessKey)
            ->setSecretKey($secretKey)
            ->setAssociateTag($associateTag)
            ->setRequest($request);

        $this->api = new ApaiIO($this->config);

    }

    public function lookupAsinByKeywords($keywords)
    {
        if (!$keywords) {
            $this->getLogger()->warning(__FUNCTION__ . ": Empty Keywords; cannot lookup ASIN");

            return "";
        }

        $search = new Search();
        $search->setCategory('All');
        $search->setKeywords($keywords);

        $this->delayFromLast(1);
        $xml = $this->api->runOperation($search);

        $parsed = simplexml_load_string($xml);

        if (!$this->isValidItemResponse($parsed)) {
            $this->getLogger()->warning(__FUNCTION__ . ": $keywords ASIN lookup had invalid response");
            $this->getLogger()->warning(var_export($parsed, true));

            return "";
        }
        if ($this->hasError($parsed)) {
            $this->getLogger()->error(__FUNCTION__ . ": Error looking up \"$keywords\":" . $this->hasError($parsed));

            return "";
        }

        $asin = (string) $parsed->Items->Item->ASIN;
        $this->getLogger()->debug("Looking up ASIN for $keywords produced $asin");

        return $asin;

    }

    protected function hasError(\SimpleXMLElement $parsed)
    {
        if ($parsed->Items->Request->Errors) {
            $message = $parsed->Items->Request->Errors->Error->Message;

            return $message;
        }

        return false;
    }

    protected function delayFromLast($seconds)
    {
        static $last = 0;
        $mtime = (microtime(true));
        $delay = $mtime - $last;
        $seconds += 0.1; //add some buffer
        if ($mtime - $last < $seconds) {
            $sleep = ($seconds * 1000000) - ($delay * 1000000);
            $this->getLogger()->debug("usleep $sleep");
            usleep($sleep) ;
        }
        $last = (microtime(true));

    }

    public function lookupImageUrlByAsin($asin)
    {
        if (!$asin) {
            $this->getLogger()->warning(__FUNCTION__ . ": Empty ASIN; cannot lookup imageUrl");

            return "";
        }

        $imageSearch = new ApaiIOImageSearch();
        $imageSearch->setAsin($asin);

        $this->delayFromLast(1);
        $xml = $this->api->runOperation($imageSearch);
        $parsed = simplexml_load_string($xml);


        if ($this->isValidItemResponse($parsed)) {
            return (string) @$parsed->Items->Item->MediumImage->URL;
        } else {
            $this->getLogger()->warning("$asin did not return a valid image response");
        }

        return "";
    }

    public function lookupPrice($asin) {


        $empty =  [
            'amount' => null,
            'price' => null,
            'currency' => null,
            'size' => null,
            'title' => null
        ];

        if (!$asin) {
            $this->getLogger()->warning(__FUNCTION__ . ": Empty ASIN; cannot lookup price");

            return $empty;
        }

        $lookup = new \ApaiIO\Operations\Lookup();
        $lookup->setItemId($asin);
        $lookup->setResponseGroup(array('Large')); // More detailed information

        $xml = $this->api->runOperation($lookup);
        $parsed = simplexml_load_string($xml);

        if (!$this->isValidItemResponse($parsed)) {
            $this->getLogger()->warning("$asin did not return a valid prices response");
            return $empty;
        }

        $price = (string)@$parsed->Items->Item->OfferSummary->LowestNewPrice->FormattedPrice;
        $rawPrice = (string)@$parsed->Items->Item->OfferSummary->LowestNewPrice->Amount;
        $currency = (string)@$parsed->Items->Item->OfferSummary->LowestNewPrice->CurrencyCode;
        $size = (string)@$parsed->Items->Item->ItemAttributes->Size;
        $title = (string)@$parsed->Items->Item->ItemAttributes->Title;

        return [
            'amount' => $rawPrice,
            'price' => $price,
            'currency' => $currency,
            'size' => $size,
            'title' => $title
        ];
    }

    protected function isValidItemResponse(\SimpleXMLElement $parsed)
    {
        if ($parsed->Items) {
            if ($parsed->Items->Request) {
                $valid = (string) $parsed->Items->Request->IsValid;

                return ($valid=="True");
            }
        }

        return false;
    }

}
