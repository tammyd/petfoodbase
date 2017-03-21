<?php

namespace PetFoodDB\Command\Scrapers;

use PetFoodDB\Command\ContainerAwareCommand;
use PetFoodDB\Command\Traits\CommandIOTrait;
use PetFoodDB\Command\Traits\DBTrait;
use PetFoodDB\Model\PetFood;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseScrapeCommand extends ContainerAwareCommand
{

    use CommandIOTrait;
    use DBTrait;

    protected $catfoodService;


    /**
     * This should be overridden by extending classes
     *
     * @return null
     */
    protected function getDBPath() {
        return null;
    }

    protected function configure()
    {
        $namespace = $this->getNamespace();
        $this
            ->setDescription($this->getDescription())
            ->setName('scrape:'.$namespace)
            ->addOption(
                'sleep',
                null,
                InputOption::VALUE_REQUIRED,
                'Time to sleep between requests, in ms'
            )->addOption(
                'url',
                null,
                InputOption::VALUE_REQUIRED,
                'Single url to scrape'
            )->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                "Debug output, not saved"
            )->addOption(
                'start',
                null,
                InputOption::VALUE_REQUIRED,
                'Url index to start at'
            )->addOption(
                'stop',
                null,
                InputOption::VALUE_REQUIRED,
                'Url index to stop after'
            )->addOption(
                'sitemap',
                null,
                InputOption::VALUE_NONE,
                'Output the urls of the sitemap'
            );

    }

    abstract public function getNamespace();
    abstract public function getScraper();

    public function getDescription()
    {
        return "Download and parse " . ucwords($this->getBrand()) . " cat food information.";
    }

    abstract public function getBrand();

    protected function dumpSitemap()
    {

        $scraper = $this->getScraper();
        $sitemap = $scraper->getSitemapUrl();
        $urls = $scraper->getSiteMapUrls();
        

        $this->output->writeln("Urls from <info>" . $sitemap . "</info>:");
        foreach ($urls as $url) {
            $this->output->writeln("  $url");
        }
    }

    protected function setupDB() {
        $dbPath = $this->getDBPath();
        if (!$dbPath) {
            $dbPath = $this->container->get('config')['db.dsn'];
        }

        if ($this->input->getOption('debug')) {
            dump($dbPath);
        }

        $db = $this->getDB($dbPath);
        $this->catfoodService = new \PetFoodDB\Service\CatFoodService($db);
        $this->catfoodService->setLogger($this->container->get('logger'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->input = $input;
        $this->output = $output;

        $this->setupDB();

        $scraper = $this->getScraper();
        $scraper->setLogger($this->container->get('logger'));
        if ($input->getOption('sleep')) {
            $scraper->setSleep($input->getOption('sleep'));
        }

        if ($input->getOption('sitemap')) {
            $this->dumpSitemap();

            return;
        }

        if ($input->getOption('url')) {
            $urls = [$input->getOption('url')];

        } else {
            $urls = $scraper->getSiteMapUrls();

        }


        $start = 0;
        $stop = count($urls);
        if (!is_null($input->getOption('start'))) {
            $start = (int) $input->getOption('start');
        }
        if (!is_null($input->getOption('stop'))) {
            $stop = (int) $input->getOption('stop');
        }
        if ($start > $stop) {
            $tmp = $start;
            $start = $stop;
            $stop = $tmp;
        }

        foreach ($urls as $i=>$url) {
            if ($i<$start) {
                continue;
            } elseif ($i > $stop) {
                $output->writeln("Reached end; stopping");
                break;
            }

            if ($scraper->isPossibleProductUrl($url)) {
                $output->writeln("Scraping $i: $url...");
                $catFood = $scraper->scrapeUrl($url);
                $this->insert($catFood);
            } else {
                $scraper->recordNonProductUrl($url);
            }
        }

        list($good, $bad, $errors) = $scraper->getUrlsScraped();

        $output->writeln("<info>Data Urls:</info>");
        foreach ($good as $url) {
            $output->writeln("\t$url");
        }

        $output->writeln("<comment>Non-Data Urls:</comment>");
        foreach ($bad as $url) {
            $output->writeln("\t$url");
        }

        $output->writeln("<comment>Error Urls:</comment>");
        foreach ($errors as $url) {
            $output->writeln("\t$url");
        }
    }

    protected function insert(PetFood $catFood = null)
    {
        if ($catFood) {
            if ($this->input->getOption('debug')) {
                dump($catFood);
            } else {
                $this->catfoodService->insert($catFood);
            }
        }
    }

}
