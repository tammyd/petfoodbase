<?php

namespace PetFoodDB\Command\Scrapers;

use PetFoodDB\Scrapers\NoSitemapScraper;
use PetFoodDB\Traits\StringHelperTrait;
use Symfony\Component\Console\Input\InputOption;

abstract class BaseNoSitemapScrapeCommand extends BaseScrapeCommand
{
    protected $baseDir = "resources/generated_sitemaps/";

    use StringHelperTrait;

    protected function configure()
    {
        parent::configure();
        $this->addOption(
            'regen',
            null,
            InputOption::VALUE_NONE,
            false
        );
    }

    abstract protected function getNewScraper();

    public function getScraper()
    {
        $scraper = $this->getNewScraper();
        $scraper->setForceRegen($this->getInput()->getOption('regen'));

        $filepath = $this->getBaseDir() . $this->generatedFileName($scraper);
        $scraper->setFilepath($filepath);

        return $scraper;
    }

    protected function generatedFileName(NoSitemapScraper $scraper)
    {
        $sitemap = $scraper->getSitemapUrl();

        if ($this->endsWith($sitemap, '/')) {
            $sitemap = substr($sitemap, 0, -1);
        }
        $filepath = str_replace(['http://', 'https://'], '', $sitemap);
        $filepath = str_replace(['.', '/', '?'], "_", $filepath);
        

        return $filepath . ".xml";
    }

    /**
     * @param string $baseDir
     *
     * @return $this
     */
    public function setBaseDir($baseDir)
    {
        $this->baseDir = $baseDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return $this->baseDir;
    }

}
