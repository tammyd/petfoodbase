<?php


namespace PetFoodDB\Command\Scrapers;


use PetFoodDB\Scrapers\Avoderm;

class TemplateCommand extends BaseNoSitemapScrapeCommand
{

    public function getNamespace()
    {
        return 'avoderm';
    }

    public function getBrand()
    {
        return 'AvoDerm';
    }

    protected function getNewScraper()
    {
        return new Avoderm(
            $this->container->get('amazon.lookup'),
            $this->container->get('manual.data'),
            $this->container->get('sitemap.utils')
        );
    }
    

}
