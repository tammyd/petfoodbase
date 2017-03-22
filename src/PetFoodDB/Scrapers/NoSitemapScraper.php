<?php

namespace PetFoodDB\Scrapers;

use PetFoodDB\Amazon\Lookup;
use PetFoodDB\Service\SitemapUtil;
use PetFoodDB\Service\YmlLookup;
use VDB\Spider\Discoverer\Discoverer;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\EventListener\PolitenessPolicyListener;
use VDB\Spider\Filter\Prefetch\RestrictToBaseUriFilter;
use VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter;
use VDB\Spider\Spider;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;

abstract class NoSitemapScraper extends BasePetFoodScraper
{
    protected $scrapeDelay = 450;
    protected $forceRegen = false;
    protected $maxDepth = 3;
    protected $sitemapUtils;
    protected $maxQueueSize = null;
    protected $discoverer;

    public function __construct(Lookup $amazon, YmlLookup $manualEntries, SitemapUtil $sitemapUtils)
    {
        parent::__construct($amazon, $manualEntries);
        $this->sitemapUtils = $sitemapUtils;
    }

    public function getSiteMapUrls()
    {
        $urls = [];

        if (!$this->getForceRegen()) {
            $readUrls = $this->sitemapUtils->readSitemap();
        } else {
            $readUrls = null;
        }

        if (is_array($readUrls)) {
            return $readUrls;
        }
        

        $spider = new Spider($this->getSitemapUrl());
        $spider->setMaxDepth($this->getMaxDepth());
        if (!is_null($this->getMaxQueueSize())) {
            $spider->setMaxQueueSize($this->getMaxQueueSize());
        }
        if (is_null($this->getDiscoverer())) {
            $spider->addDiscoverer(new XPathExpressionDiscoverer("//a"));
        } else {
            $spider->addDiscoverer($this->getDiscoverer());
        }
        $spider->addPreFetchFilter(new RestrictToBaseUriFilter($this->getSitemapBaseUrl()));
        $spider->addPreFetchFilter(new UriWithHashFragmentFilter());

        $politenessPolicyEventListener = new PolitenessPolicyListener(250);
        $spider->getDispatcher()->addListener(
            SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,
            array($politenessPolicyEventListener, 'onCrawlPreRequest')
        );

        $result = $spider->crawl();

        $downloaded = $spider->getPersistenceHandler();
        foreach ($downloaded as $resource) {
            if (method_exists($resource, 'getUri')) {
                $urls[] = $resource->getUri();
            }
        }

        $this->sitemapUtils->writeSitemap($urls);

        return $urls;
    }

    /**
     * Get the base url of the sitemap url
     *
     * @return string
     */
    protected function getSitemapBaseUrl()
    {
        return $this->getBaseUrl($this->getSitemapUrl());
    }

    protected function getBaseUrl($url)
    {
        $parsed = parse_url($url);

        if (!isset($parsed['scheme']) || !isset($parsed['host'])) {
            return null;
        }

        return $parsed['scheme']."://".$parsed['host'];
    }

    /**
     * @param int $scrapeDelay
     *
     * @return $this
     */
    public function setScrapeDelay($scrapeDelay)
    {
        $this->scrapeDelay = (int) $scrapeDelay;

        return $this;
    }

    /**
     * @return int
     */
    public function getScrapeDelay()
    {
        return $this->scrapeDelay;
    }

    /**
     * @param boolean $forceRegen
     *
     * @return $this
     */
    public function setForceRegen($forceRegen)
    {
        $this->forceRegen = $forceRegen;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getForceRegen()
    {
        return $this->forceRegen;
    }

    /**
     * @param string $filepath
     *
     * @return $this
     */
    public function setFilepath($filepath)
    {
        $this->sitemapUtils->setLocation($filepath);

        return $this;
    }

    /**
     * @return string
     */
    public function getFilepath()
    {
        return $this->sitemapUtils->getLocation();
    }

    /**
     * @param int $maxDepth
     *
     * @return $this
     */
    public function setMaxDepth($maxDepth)
    {
        $this->maxDepth = min(1, (int) $maxDepth);

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxDepth()
    {
        return $this->maxDepth;
    }

    /**
     * @param null $maxQueueSize
     *
     * @return $this
     */
    public function setMaxQueueSize($maxQueueSize)
    {
        $this->maxQueueSize = $maxQueueSize;

        return $this;
    }

    /**
     * @return null
     */
    public function getMaxQueueSize()
    {
        return $this->maxQueueSize;
    }

    /**
     * @param mixed $discoverer
     *
     * @return $this
     */
    public function setDiscoverer(Discoverer $discoverer)
    {
        $this->discoverer = $discoverer;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDiscoverer()
    {
        return $this->discoverer;
    }

}
