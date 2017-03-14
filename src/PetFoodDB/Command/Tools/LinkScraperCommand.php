<?php


namespace PetFoodDB\Command\Tools;

use PetFoodDB\Command\ContainerAwareCommand;
use PetFoodDB\Traits\StringHelperTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

class LinkScraperCommand extends ContainerAwareCommand
{

    use StringHelperTrait;

    /**
     * @var Client $client
     */
    protected $client;

    protected $url;

    protected $output;

    protected function configure()
    {
        $this
            ->setDescription("Scrape arbitrary http address for urls")
            ->setName('page:urls')
            ->addArgument("url", InputArgument::REQUIRED, "Url to scrape");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;
        $this->url = $input->getArgument('url');
        $this->client = new Client();
        $crawler = $this->getUrlCrawler($this->url);
        $links = $this->getAllLinks($crawler);
        //$links = $this->filterLinks($links);

        sort($links);
        $links = array_unique($links);

        foreach ($links as $link) {
            $this->output->writeln($link);
        }

    }


    protected function getAllLinks(Crawler $crawler) {

        $links = [];
        $crawler = $crawler->filter('a');
        $crawler->each(function($node) use (&$links) {
            $links[] = $node->attr('href');
        });

        return $links;
        
    }

    protected function filterLinks(array $links) {
        $host = $this->getHost($this->url);

        $filtered = array_filter($links, function($link) use ($host) {
            $contains = $this->contains($link, $host, false);
            return !$contains;
        });
        $filtered = array_filter($filtered, function ($link) {
            return !($this->startsWith($link, "/"));
        });

        $filtered = array_filter($filtered, function ($link) {
            return !($this->startsWith($link, "#"));
        });

        return $filtered;
    }

    protected function getHost($Address) {
        $host = parse_url(trim($Address), PHP_URL_HOST);

        $pieces = explode(".", $host);
        $pieces = array_slice($pieces, -2, 2);
        return implode(".", $pieces);
    }

    protected function getUrlCrawler($url)
    {
        $crawler = $this->client->request('GET', $url);
        return $crawler;
    }


}
