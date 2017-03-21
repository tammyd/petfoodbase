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

    public function fourOrFourAction() {

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
        
        $params = [
            'minimalMobile' => false,
            'homeNavClass' => 'active',
            'recentUpdates' => $this->getRecentUpdates(),
            'amazonQuery' => 'cat supplies',
            'brands' => $allBrands,
            'popular' => $popularBrands,
            'seo' => $this->getBaseSEO()
        ];

        $this->render('home.html.twig', $params);
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
    
    protected function getRecommendations() {
        $html = <<< EOF
        I’m often asked about the products I use with my three cats, Kilo, Echo & Zulu. Over the years we’ve tried many different foods, litter, and toys, and we definitely have our favorites. Below is a sample of those products we use regularly and recommend.
EOF;

        $postData = [
            'title' => "Other Recommendations",
            'slug' => "resources",
            'url' => "/resources",
            'html' => $html
        ];

        return $postData;
    }

    protected function getOrigins() {
        $aboutHTML = <<< EOF
        I first got interested in pet nutrition, and specifically cat food nutrition, after visiting the pet food store one afternoon. I had returned from my vet’s office earlier that morning, and during my visit she mentioned a number of ingredients she felt my cats should avoid.
While in the pet food store I spent a very long time reading tiny ingredient lists on many different varieties of cat food, trying to find the ones without the offending words. At the time I also only vaguely understood that carbs were generally less desirable in cat food, and that I should be looking for foods higher in protein.
        I left the store that day without purchasing anything. I couldn’t decide what was the “best” food for my much adored three black cats that would meet my vets recommendations. I returned home, and turned to my trusty friend Google.
EOF;

        $postData = [
            'title' => "The Origins of CatFoodDB",
            'slug' => "/about",
            'url' => "about",
            'html' => $aboutHTML
        ];

        return $postData;
    }

    public function aboutAction() {
        $params = [
            'amazonQuery' => "cat toys",
            'aboutNavClass'=>'active'
        ];
        $this->render('about.html.twig', $params);
    }

    public function resourcesAction() {
        $params = [
            'amazonQuery' => "cat trees",
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
        $this->render('faq.html.twig', ['faqNavClass'=>'active', 'seo'=>$seo, 'amazonQuery' => "cat treats"]);
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
        if (!$products) {
            $this->app->notFound();
        }

        $wet = [];
        $dry = [];
        foreach ($products as $product) {
            $stats = $analysis->getProductAnalysis($product);
            $product->addExtraData('stats', $stats);
            $product = $productController->getAllProductDetails($product);
            if ($product->getIsWetFood()) {
                $wet[] = $product;
            } else {
                $dry[] = $product;
            }
        }

        usort($wet, [$this, 'rankProduct']);
        usort($dry, [$this, 'rankProduct']);


        $wetRating = $this->calculateAverageRating($wet);
        $dryRating = $this->calculateAverageRating($dry);

        $brandId = $this->cleanText(strtolower($products[0]->getBrand()));
        $amazonTemplate = "partials/brands/amazon/$brandId.html.twig";
        $infoTemplate = "partials/brands/$brandId.html.twig";

        $brandInfo = $brandAnalysis->getBrandData($brand);

        $data = [
            'img' => $brandId,
            'brand' => $this->get('brand.info')->getBrandInfo($products[0]->getBrand()),
            'brandInfo' => $brandInfo,
            'wet'=>$wet, 'dry'=>$dry,
            'wetRating' => $wetRating, 'dryRating' => $dryRating,
            'seo'=>$this->getBrandSEO($products),
            'reviewNavClass' => 'active',
            'amazonQuery' => $products[0]->getBrand() . " cat food",
            'template' => $this->templateExists($infoTemplate) ? $infoTemplate : null,
//            'amazonTemplate' => $this->templateExists($amazonTemplate) ? $amazonTemplate : null,
            'amazonQuery' => "$brandId cat food"
            
        ];

        $this->render('brand.html.twig', $data);
    }

    public function rankProduct($productA, $productB) {
        $scoreA = $productA->getExtraData('stats')['nutrition_rating'] + $productA->getExtraData('stats')['ingredients_rating'];
        $scoreB = $productB->getExtraData('stats')['nutrition_rating'] + $productB->getExtraData('stats')['ingredients_rating'];

        return ($scoreA < $scoreB) ? 1 : -1;
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
        $title = $brandInfo['official_name'] .  " Cat Food Reviews";
        $title = $this->removeDups($title, "Cat");

        $seo['shortTitle'] = $title;
        $seo['title'] = "CatFoodDB - $title";
        $seo['url'] = $this->seoService->getBaseUrl() . "/" . $this->getRequest()->getResourceUri();
        $seo['description'] = sprintf("%s cat food reviews from CatFoodDB -- Includes nutritional analysis, ingredient lists, allergen alerts and more.", $brand);
        

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
            $urls[] = sprintf("%s/product/%s", $baseUrl, htmlspecialchars($product->getProductPath()));
        }

        $brandUrls = $this->getBrandPageUrls();

        foreach ($brandUrls as $section=>$urlData) {

            foreach ($urlData as $name=>$url) {

                if ($this->startsWith($url, "/")) {
                    $uri = sprintf("%s%s", $baseUrl, htmlspecialchars($url));
                } else {
                    $uri = sprintf("%s/%s", $baseUrl, htmlspecialchars($url));
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


    protected function getRecentlyUpdatedBrands() {

        $limit = 5;

        $recentlyUpdated = $this->catFoodService->getRecentlyUpdatedBrands();
        $updatedBrands = [];
        foreach ($recentlyUpdated as $brand) {
            $updatedBrands[$brand['name']] = "/brand/" . $brand['brand'];
        }

        return array_splice($updatedBrands, 0, $limit);


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
            'updatedBrands' => $this->getRecentlyUpdatedBrands(),
            'articleUrls' => $this->getArticlePageUrls(),
            'minimalMobile' => true,
            'shareText' => urlencode($baseSeo['title']),
            'shareImage' => urlencode($baseSeo['siteImage']),

        ];

        $pageData = array_merge($defaultData, $data);

        $this->app->response->headers->replace(['Cache-Control'=>"public, s-maxage=$cache max-age=$cache"]);
        
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
    

    protected function getBrandPageUrls() {
        $brands = $this->catFoodService->getBrands();

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
        if ($product->getIsWetFood()) {
            $query = "wet cat food";
        } else {
            $query = "dry cat food";
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
