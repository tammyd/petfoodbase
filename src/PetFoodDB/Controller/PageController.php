<?php

namespace PetFoodDB\Controller;

use PetFoodDB\Model\PetFood;
use PetFoodDB\Service\AnalyzeIngredients;
use PetFoodDB\Traits\ArrayTrait;
use PetFoodDB\Traits\StringHelperTrait;
use PetFoodDB\Controller\BaseController;
use Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\VarDumper\VarDumper;

class PageController extends BaseController
{

    use ArrayTrait, StringHelperTrait;

    protected $petType = 'cat';

    protected $stats;

    //services
    protected $catFoodService;

    /**
     * PageController constructor.
     * @param \Slim\Slim $app
     */
    public function __construct(\Slim\Slim $app)
    {
        parent::__construct($app);
        $this->catFoodService = $this->get('catfood');
        $this->stats = $this->catFoodService->getStats();

        $authKey = $this->get('api.auth')->getInitAuthKey();
        $_SESSION['authKey'] = $authKey;


    }

    /**
     * @return string
     */
    public function getPetType()
    {
        return $this->petType;
    }

    /**
     * @param string $petType
     * @return $this
     */
    public function setPetType($petType)
    {
        $this->petType = $petType;
        return $this;
    }

    static function makeChewyAffiliateUrl($pubref, $chewyPath) {
        $https = urlencode("https://www.chewy.com/");
        $template = "https://prf.hn/click/camref:1011l4bA9/pubref:%s/destination:%s%s";

        $result = sprintf($template, $pubref, $https, $chewyPath);
        return $result;
    }

    public function chewyAction($pubref, $chewyPath) {

        $redirectTo = self::makeChewyAffiliateUrl($pubref, $chewyPath);
        $this->app->redirect($redirectTo);
    }



    public function fourOhFourAction() {

        if ($this->getRedirect()) {
            $redirect = $this->getRedirect();
            $this->app->redirect($redirect[0], $redirect[1]);
        }

        $brands = $this->getBrandPageUrls();
        $allBrands = [];
        foreach ($brands as $section=>$brandData) {
            foreach ($brandData as $brand=>$url) {
                $allBrands[$brand] = $url;
            }
        }

        $seo = $this->getBaseSEO();
        $seo['shortTitle'] = "404 : CatFoodDB";
        $seo['title'] = "404 : " . $seo['title'];

        $this->render('404.html.twig', [
            'brands'=>$allBrands,
            'seo' => $seo

        ]);
    }

    public function getRedirect() {

        $service = $this->get('redirector.service');
        $currentPath = $this->app->request->getResourceUri();

        return $service->getRedirectFor($currentPath);
    }

    
    public function homeAction() {

        $brands = $this->getBrandPageUrls();

        $allBrands = [];
        foreach ($brands as $section=>$brandData) {
            foreach ($brandData as $brand=>$url) {
                $allBrands[$brand] = $url;
            }
        }


        $popularBrandInfo = $this->catFoodService->getPopularBrands();
        $popularBrands = [];
        foreach ($popularBrandInfo as $brand) {
            $popularBrands[$brand['name']] = "/brand/" . $brand['brand'];
        }

        $articles = $this->getArticleBlogPosts();

        $params = [
            'minimalMobile' => false,
            'homeNavClass' => 'active',
            'recentUpdates' => $this->getRecentUpdates(),
            'updatedBrands' => $this->catFoodService->getRecentlyUpdatedBrands(),
            'amazonQuery' => sprintf("%s supplies", strtolower($this->getPetType())),
            'brands' => $allBrands,
            'popular' => $popularBrands,
            'seo' => $this->getBaseSEO(),
            'articles' => $articles
        ];


        $this->render('home.html.twig', $params);
    }

    protected function getArticleBlogPosts() {
        $blog = $this->get('blog.service');
        $posts = $blog->getBlogPosts();
        $posts = array_filter($posts, function ($p) {
            $meta = $p->getYaml();
            return !$meta['isBestOf'];
        });

        return $posts;
    }

    protected function getRecentUpdates() {
        $blog = $this->get('blog.service');
        $blogController = $this->get('blog.controller');
        $posts = $blog->getBlogPosts();
        $recents = [];
        foreach ($posts as $post) {
            $recents[] = $blogController->buildPostData($post);
        }
        
        $recents = array_slice($recents, 0, 4); //make sure we only get 4

        return $recents;
    }


    public function aboutAction() {
        $params = [
            'amazonQuery' => sprintf("%s toys", strtolower($this->getPetType())),
            'aboutNavClass'=>'active'
        ];
        $this->render('about.html.twig', $params);
    }

    public function resourcesAction() {
        $params = [
            'amazonQuery' => sprintf("%s trees", strtolower($this->getPetType())),
            'resourcesNavClass'=>'active',
            'seo' => $this->seoService->getResourcesSEO()
        ];
        $this->render('resources.html.twig', $params);
    }

    /**
     *  Action for the search single page app
     */
    public function searchAction()
    {
        $this->render('spa.html.twig', ['admin' => $this->isAdmin(), 'searchNavClass'=>'active', 'angularClasses' => 'angular-dropdown']);
    }
    
    public function faqAction() {
        $seo = $this->getFAQSEO();
        $this->render('faq.html.twig', ['faqNavClass'=>'active', 'seo'=>$seo, 'amazonQuery' => sprintf("%s treats", strtolower($this->getPetType()))]);
    }



    protected function getAllProductsByBrand() {
        $analysis = $this->get('analysis.access');
        $all = $this->catFoodService->getAll();
        foreach ($all as $product) {
            $stats = $analysis->getProductAnalysis($product);
            $product->addExtraData('stats', $stats);
            
            $brand = $product->getBrand();
            if (!isset($productsByBrand[$brand])) {
                $productsByBrand[$brand] = [];
            }
            $productsByBrand[$brand][] = $product;
        }
        ksort($productsByBrand);

        return $productsByBrand;
    }
    

    /**
     * Action supporting a single brand's page
     * @param string $brand
     */
    public function brandAction($brand) {

        $analysis = $this->get('analysis.access');
        $brandAnalysis = $this->get('brand.analysis');

        $products = $this->catFoodService->getByBrand($brand);

        $productController = $this->get('product.controller');
        $shopService = $this->getContainer()->get('shop.service');
        if (!$products) {
            $this->app->notFound();
        }

        $wet = [];
        $dry = [];
        $discontinued = [];
        foreach ($products as $product) {
            $stats = $analysis->getProductAnalysis($product);

            $shop = $shopService->getAll($product->getId());
            $product->addExtraData('shopUrls', $shop);

            $product->addExtraData('stats', $stats);
            $product = $productController->getAllProductDetails($product);

            if ($product->getDiscontinued()) {
                $discontinued[] = $product;
            }
            else {
                if ($product->getIsWetFood()) {
                    $wet[] = $product;
                } else {
                    $dry[] = $product;
                }
            }
        }

        usort($wet, [$this, 'rankByName']);
        usort($dry, [$this, 'rankByName']);
        usort($discontinued, [$this, 'rankByName']);

        $dryPurchaseInfo = $brandAnalysis->hasAnyPurchaseInfo($brand, 'dry');
        $wetPurchaseInfo = $brandAnalysis->hasAnyPurchaseInfo($brand, 'wet');

        $wetRating = $this->calculateAverageRating($wet);
        $dryRating = $this->calculateAverageRating($dry);

        $brandId = $this->cleanText(strtolower($products[0]->getBrand()));
        $infoTemplate = "partials/brands/$brandId.html.twig";

        $brandData = $brandAnalysis->getBrandData($brand);
        $brandInfo = $this->get('brand.info')->getBrandInfo($products[0]->getBrand());
        $brandRating = $brandAnalysis->rateBrand($brand);

        $chewyUrl = $this->getChewyBrandUrl($brandInfo, array_merge($wet, $dry));
        $brandInfo['chewy'] = $chewyUrl;

        $lastUpdated = $this->daysSinceAppProductsUpdated(array_merge($wet, $dry));


        $data = [
            'img' => $brandId,
            'brand' => $brandInfo,
            'brandInfo' => $brandData,
            'wet'=>$wet,
            'dry'=>$dry,
            'discontinued' => $discontinued,
            'wetRating' => $wetRating,
            'dryRating' => $dryRating,
            'seo'=>$this->getBrandSEO($products),
            'reviewNavClass' => 'active',
            'template' => $this->templateExists($infoTemplate) ? $infoTemplate : null,
            'amazonQuery' => sprintf("%s %s food", $brandId, $this->getPetType()),
            'brandRating' => $brandRating,
            'hideDryProductPrices' => !$dryPurchaseInfo,
            'hideWetProductPrices' => !$wetPurchaseInfo,
            'chewySource' => $this->makeChewySource($brand),
            'isVetBrand' => $this->isBrandAllVet(array_merge($wet, $dry)),
            'lastUpdated' => $lastUpdated
            
        ];
        

        $this->render('brand.html.twig', $data);
    }

    protected function makeChewySource($brand) {
        $source = strtolower($brand);
        $source = $this->stripHtmlSuperscripts($source);
        $source = $this->stripNonUTF8($source);
        $source = trim($this->removeMultipleSpaces($source));
        $source = str_replace(' ', '_', $source);
        return "brand_$source";

    }

    protected function isBrandAllVet($products) {
        foreach ($products as $prod) {
            if (!$prod->getVeterinary()) {
                return false;
            }
        }
        return true;
    }

    protected function daysSinceAppProductsUpdated($products) {
        $lastUpdated = null;
        foreach ($products as $prod) {
            $date = strtotime($prod->getUpdated());
            if (is_null($lastUpdated) || $date < $lastUpdated) {
                $lastUpdated = $date;
            }
        }
        $origin = new \DateTime(date("M Y", $lastUpdated));
        $target = new \DateTime(date("M Y"));

        $interval = $origin->diff($target);
        return $interval->days;
    }

    protected function getChewyBrandUrl($brandInfo, $products) {
        $hasChewy = false;
        foreach ($products as $prod) {
            $shopUrls = $prod->getExtraData('shopUrls');
            if (isset($shopUrls['chewy']) && $shopUrls['chewy']) {
                $hasChewy = true;
                break;
            }
        }

        if ($hasChewy) {
            $chewyUrl = sprintf("https://www.chewy.com/s?query=%s+cat+food", $brandInfo['official_name']);
            return $chewyUrl;
        } else {
            return null;
        }
    }

    //rank by id
    public function rankByName($productA, $productB) {
        $n1 = $productA->getFlavor();
        $n2 = $productB->getFlavor();
        if ($n1==$n2) {
            return 0;
        } else {
            return ($n1 > $n2) ? 1 : -1;
        }
    }


    //rank by score, but discontinued products always rank lower
    public function rankProduct($productA, $productB) {
        $scoreA = $productA->getExtraData('stats')['nutrition_rating'] + $productA->getExtraData('stats')['ingredients_rating'];
        $scoreB = $productB->getExtraData('stats')['nutrition_rating'] + $productB->getExtraData('stats')['ingredients_rating'];

        $da = $productA->getDiscontinued();
        $db = $productB->getDiscontinued();


        if (($da && $db) || (!$da && !$db)) {
            if ($scoreA == $scoreB) {
                return 0;
            }
            return ($scoreA < $scoreB) ? 1 : -1;
        }
        else if ($da) {
            return 1;
        }
        else if ($db) {
            return -1;
        }

        return 0;
    }

    public function calculateAverageRating(array $products) {
        
        $service =  $this->get('catfood');
        return $service->calculateAverageRating($products);

    }

    /**
     * Get the SEO array for the faq
     *
     * @return array
     */
    protected function getFAQSEO() {

        return $this->seoService->getFAQSEO();
    }

    protected function getBrandSEO(array $products) {

        $dryProducts = array_filter($products, function($p) {
            return $p->getIsDryFood();
        });
        $wetProducts = array_filter($products, function($p) {
            return $p->getIsDryFood();
        });


        $brandInfo = $this->get('brand.info')->getBrandInfo($products[0]->getBrand());
        $seo = $this->getBaseSEO();
        $brand = ucwords($products[0]->getBrand());
        $capPet = ucwords($this->getPetType());
        $title = sprintf("%s %s Food Reviews", $brandInfo['official_name'], $capPet );

        $title = $this->removeDups($title, $capPet);
        $site = $capPet."FoodDB";

        $seo['shortTitle'] = $title;
        $seo['title'] = $capPet . "FoodDB - $title";
        $seo['url'] = $this->seoService->getBaseUrl() . "/" . $this->getRequest()->getResourceUri();

        $food = strtolower($this->getPetType());

        $seo['description'] = sprintf("%s $food food reviews from $site -- Includes nutritional analysis, ingredient lists, allergen alerts and more.", $brand);
        

        return $seo;
    }

    protected function removeDups($title, $dup) {

        $search = "$dup $dup";
        $title = str_replace($search, $dup, $title);
        return $title;

    }


    protected function buildAllergenData(PetFood $product) {
        
        $allergens = $this->get('catfood.analysis')->getIngredientService()->containsAllergens($product);

        $allAllergens = $allergens['all'];
        unset($allergens['all']);

        $specificAllergens = array_keys($allergens);
        foreach ($specificAllergens as $i=>$key) {
            if (count($allergens[$key]) == 0) {
                unset($specificAllergens[$i]);
            }
        }
        $allergenData = [
            'allergens' => $allergens,
            'allergenList' => $allAllergens,
            'specificAllergens' => $specificAllergens
        ];

        return $allergenData;
    }
    
    
    /**
     * Sitemap action
     */
    public function sitemapAction()
    {
        $this->app->contentType("text/xml");
        $baseUrl = $this->getRequest()->getUrl();
        $sitemapUtls = $this->get('sitemap.utils');

        if ($this->endsWith($baseUrl, "/")) {
            $baseUrl = substr($baseUrl, 0, count($baseUrl) - 1);
        }


        $all = $this->catFoodService->getAll();

        $urls = [
            $baseUrl,
            sprintf("%s/faq", $baseUrl),
            sprintf("%s/search", $baseUrl),
            sprintf("%s/about", $baseUrl),
            sprintf("%s/resources", $baseUrl),
            sprintf("%s/blog", $baseUrl)
        ];

        foreach($all as $i => $product) {
            $path = htmlspecialchars($product->getProductPath());
            if ($this->contains($path, "%E2_%A2")) {
                $path = str_replace("%E2_%A2", "™", $path);
            }

            $urls[] = sprintf("%s/product/%s", $baseUrl, $path);
        }

        $brandUrls = $this->getBrandPageUrls(true);

        foreach ($brandUrls as $section=>$urlData) {

            foreach ($urlData as $name=>$url) {
                $url = htmlspecialchars($url);
                if ($this->contains($url, "%E2_%A2")) {
                    $url = str_replace("%E2_%A2", "™", $url);
                }


                if ($this->startsWith($url, "/")) {
                    $uri = sprintf("%s%s", $baseUrl, $url);
                } else {
                    $uri = sprintf("%s/%s", $baseUrl, $url);
                }

                $urls[] = $uri;
            }
        }

        $articleUrls = $this->getArticlePageUrls();
        foreach ($articleUrls as $url) {

            if ($this->startsWith($url['url'], "/")) {
                $uri = sprintf("%s%s", $baseUrl, htmlspecialchars($url['url']));
            } else {
                $uri = sprintf("%s/%s", $baseUrl, htmlspecialchars($url['url']));
            }

            $urls[] = $uri;
        }

        $sitemap = $sitemapUtls->buildSitemap($urls, 'weekly');

        $this->getResponse()->setBody($sitemap);
    }




    /**
     * Render any page with seo, etc
     *
     * @param string $template
     * @param array $data
     */
    protected function render($template, $data = []) {
        if ($this->getParameter('app.debug')) {
            $cache = 15; //15 second cache
        } else {
            $cache = 3 * 60 * 60; //3 hour cache
        }


        $baseSeo = $this->getBaseSEO();
        $defaultData = [
            'stats' => $this->stats,
            'seo' => $this->getBaseSEO(),
            'brandUrls' => $this->getBrandPageUrls(),
            'updatedBrands' => $this->catFoodService->getRecentlyUpdatedBrands(),
            'articleUrls' => $this->getArticlePageUrls(),
            'minimalMobile' => true,
            'shareText' => urlencode($baseSeo['title']),
            'shareImage' => urlencode($baseSeo['siteImage']),

        ];

        $pageData = array_merge($defaultData, $data);

        $this->app->response->headers->replace(['Cache-Control'=>"max-age=$cache, public"]);

        $this->app->render($template, $pageData);
    }

    protected function getArticlePageUrls() {
        $blogService = $this->get('blog.service');
        $posts = $blogService->getBlogPosts();
        $urls = [];
        foreach ($posts as $post) {
            $urls[] = [
                'title' => $post->getYAML()['title'],
                'url' => $blogService->getPostUrl($post->getYAML()['slug'])
            ];
        }

        return $urls;
    }
    

    protected function getBrandPageUrls($discontinued = false) {
        $brands = $this->catFoodService->getBrands($discontinued);

        usort($brands, function($left, $right) {
            return strcasecmp($left['name'], $right['name']);
        });

        $urls = [
            '0-a' => [],
            'b-c' => [],
            'd-e' => [],
            'f-g' => [],
            'h-i' => [],
            'j-k' => [],
            'l-m' => [],
            'n-o' => [],
            'p-q' => [],
            'r-s' => [],
            't-u' => [],
            'v-w' => [],
            'x-z' => []
        ];
        foreach ($brands as $brandInfo) {
            $name = $brandInfo['name'];
            $brand = $brandInfo['brand'];

            if (strtolower($name) < 'b') {
                $key = '0-a';
            } elseif (strtolower($name) < 'd') {
                $key = 'b-c';
            } elseif (strtolower($name) < 'f') {
                $key = 'd-e';
            } elseif (strtolower($name) < 'h') {
                $key = 'f-g';
            } elseif (strtolower($name) < 'j') {
                $key = 'h-i';
            } elseif (strtolower($name) < 'l') {
                $key = 'j-k';
            } elseif (strtolower($name) < 'n') {
                $key = 'l-m';
            } elseif (strtolower($name) < 'p') {
                $key = 'n-o';
            } elseif (strtolower($name) < 'r') {
                $key = 'p-q';
            } elseif (strtolower($name) < 't') {
                $key = 'r-s';
            } elseif (strtolower($name) < 'v') {
                $key = 't-u';
            } elseif (strtolower($name) < 'x') {
                $key = 'v-w';
            } else {
                $key = 'x-z';
            }

            $urls[$key][$name] = "/brand/$brand";
        }



        return $urls;
    }

    protected function getAmazonSearchQuery(PetFood $product)
    {
        $food = strtolower($this->getPetType());
        if ($product->getIsWetFood()) {
            $query = "wet $food food";
        } else {
            $query = "dry $food food";
        }

        $analysis = $this->getContainer()->get('catfood.analysis');
        $ingredientsAnalysis = $analysis->getIngredientService();

        $protein = "";
        for ($i = 0; $i < 5; $i++) {
            $protein = $ingredientsAnalysis::isProteinSource($product, $i + 1);
            break;
        }
        if ($protein) {
            $adjectives = $ingredientsAnalysis::getProteinAdjectives();
            foreach ($adjectives as $adj) {
                $protein = trim(str_replace($adj, "", $protein));
            }

            $query = $protein . " $query";
        }

        return $query;

    }
    

}
