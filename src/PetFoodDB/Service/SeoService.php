<?php


namespace PetFoodDB\Service;


use PetFoodDB\Model\PetFood;
use PetFoodDB\Traits\ArrayTrait;
use PetFoodDB\Twig\CatFoodExtension;

class SeoService
{
    use ArrayTrait;

    protected $baseUrl;

    public function __construct($baseUrl, CatFoodExtension $urlHelper)
    {
        $this->baseUrl = $baseUrl;
        $this->urlHelper = $urlHelper;
    }


    public function getBaseSEO()
    {

        //these values are defined in base.html.twig
        //todo - make these config, and pull in base from config as well
        $seo = [
            'shortTitle' => 'CatFoodDB - Unbiased Cat Food Reviews',
            'title' =>  'CatFoodDB - Cat Food Reviews to help you find the best cat food for your cat',
            'description' => 'Cat food reviews for over 2000 wet and dry cat food products. Includes product analysis, ingredient lists, nutritional breakdown and calorie counts.',
            'url' => $this->baseUrl,
            'siteImage' => $this->baseUrl . '/img/logo/logo-purple.jpg',
            'twitter' => '@catfooddb',
            'siteName' => 'CatFoodDB',
            'siteUrl' => $this->baseUrl
        ];

        return $seo;
    }

    public function getPostSEO(array $meta) {
        $slug = $this->getArrayValue($meta, 'slug');
        $url = $this->baseUrl . "/blog/" . $slug;
        $seo = $this->getBaseSEO();
        $title = strip_tags($this->getArrayValue($meta, 'title', $slug));
        $seo['title'] = $title;
        $seo['shortTitle'] = $title;
        $seo['siteUrl'] = $url;
        $seo['url'] = $url;
        $seo['description'] = $this->getArrayValue($meta, 'seo_description');

        $image = $this->getArrayValue($meta, 'image');
        if ($image) {
            $seo['siteImage'] = $this->baseUrl . $image;
        }

        return $seo;

    }
    /**
     * Get the SEO array for the faq
     *
     * @return array
     */
    public function getFAQSEO() {

        $seo = $this->getBaseSEO();
        $seo['siteUrl'] = $this->baseUrl . "/faq";
        $seo['shortTitle'] = "CatFoodDB - FAQ";
        $seo['title'] = $seo['shortTitle'];
        $seo['description'] = "CatFoodDB - Frequently Asked Questions";

        return $seo;
    }

    public function getResourcesSEO() {
        $seo = $this->getBaseSEO();
        $seo['shortTitle'] = "CatFoodDB - Resources & Recommendations";
        $seo['title'] = $seo['shortTitle'];
        $seo['description'] = "Resources & Recommendations for other cat products, including toys, trees, litter boxes and cat carriers from the human behind CatFoodDB.";
        $seo['siteUrl'] = $this->baseUrl . "/resources";

        return $seo;
    }


    /**
     * Get the SEO array for a single petfood
     *
     * @param PetFood $product
     * @return array
     */
    public function getProductSEO(PetFood $product) {

        $title = $product->getDisplayName() . " Cat Food Review";
        if (strpos(strtolower($product->getDisplayName()), "cat food") > 0) {
            $title = $product->getDisplayName() . " Review"; //dont duplicate "cat food"
        }


        $seo = $this->getBaseSEO();
        $seo['shortTitle'] = $title;
        $seo['title'] = $title;
        $seo['description'] = sprintf("%s, analysis, ingredient list, nutritional information and calories", $title);
        $seo['url'] = $this->baseUrl . $this->urlHelper->catfoodUrl($product);
        $seo['type'] = 'product';

        if ($product->getImageUrl()) {
            $seo['siteImage'] = $product->getImageUrl();
        }

        return $seo;
    }

    public function getBaseUrl() {
        return $this->baseUrl;
    }

}
