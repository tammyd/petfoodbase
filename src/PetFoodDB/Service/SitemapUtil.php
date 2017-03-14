<?php

namespace PetFoodDB\Service;

class SitemapUtil
{
    protected $location;

    public function writeSitemap(array $urls, $changeFreq=null)
    {
        $sitemap = $this->buildSitemap($urls, $changeFreq);

        file_put_contents($this->getLocation(), $sitemap);
    }

    public function buildSitemap(array $urls, $changeFreq=null)
    {
        $header =<<<END
<?xml version="1.0" encoding="UTF-8"?>
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
END;

        if ($changeFreq) {
            $urlTempl =<<<END
<url>
  <loc>%s</loc>
  <changefreq>%s</changefreq>
</url>

END;

        } else {
            $urlTempl =<<<END
<url>
  <loc>%s</loc>
</url>

END;
        }


        $footer =<<<END
</urlset>
END;

        $sitemap = $header;
        foreach ($urls as $url) {
            if ($changeFreq) {
                $sitemap .= sprintf($urlTempl, $url, $changeFreq);
            } else {
                $sitemap .= sprintf($urlTempl, $url);
            }

        }

        $sitemap .= $footer;

        return $sitemap;
    }

    public function parseSitemapXmlIntoUrls(\SimpleXMLElement $xml)
    {
        $urls = [];

        foreach ($xml->url  as $url) {
            $urls[] = (string) $url->loc;
        }

        return $urls;
    }

    public function readSitemap()
    {
        if (!file_exists($this->getLocation())) {
            return null;
        }

        $content = file_get_contents($this->getLocation());

        if ($content===false) {
            return false;
        }

        $content = str_replace('&', '&amp;', $content);
        $xml = simplexml_load_string($content);

        return $this->parseSitemapXmlIntoUrls($xml);
    }

    /**
     * @param mixed $location
     *
     * @return $this
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

}
