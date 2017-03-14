<?php
namespace PetFoodDB\Command\Scrapers\CustomFilters;

use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\Uri\FilterableUri;
use VDB\Uri\UriInterface;


class IgnoreKeywordInUrlFilter implements PreFetchFilter
{
    private $ignored;

    /**
     * @param string $seed
     */
    public function __construct(array $ignoredWords)
    {
        $this->ignored = $ignoredWords;
    }

    public function match(FilterableUri $uri)
    {
        /*
         * if the URI contains the keywords, it is not allowed
         */
        $strUri = $uri->toString();
        foreach ($this->ignored as $skip) {

            if (stripos($strUri, $skip) !== false) {
                $uri->setFiltered(true, "$strUri contained ignored word $skip");
                return true;
            }

        }

        return false;
    }
}
