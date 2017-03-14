<?php


namespace PetFoodDB\Scrapers;


use Symfony\Component\DomCrawler\Crawler;

class Template extends NoSitemapScraper
{

    public function getSitemapUrl()
    {
        return "http://www.avodermnatural.com/all-products/cat-foods";
    }

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
        return [];

    }

}
