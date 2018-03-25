<?php


namespace PetFoodDB\Controller;


use PetFoodDB\Model\PetFood;
use PetFoodDB\Service\AnalysisWrapper;
use PetFoodDB\Service\AnalyzeIngredients;
use PetFoodDB\Service\PetFoodService;


class ProductPageController extends PageController
{
    
    /**
     * Action supporting a single projects page
     *
     * @param string $brand
     * @param string $slug
     */
    public function productAction($brand, $slug) {

        $product = $this->catFoodService->getByBrandSlug($brand, $slug);

        $this->renderProduct($product);
    }

    /**
     * Action supporting a single project's page
     * @param int $productId
     */
    public function productIdAction($productId) {

        $product = $this->catFoodService->getById((int)$productId);

        $this->renderProduct($product);
    }

    /**
     * Action supporting a single project's page
     * @param int $productId
     */
    public function productEmbedAction($productId) {

        $product = $this->catFoodService->getById((int)$productId);

        $this->renderProduct($product, 'product-embed.html.twig');
    }




    /**
     * Get the SEO array for a single catfood
     *
     * @param PetFood $product
     * @return array
     */
    protected function getProductSEO(PetFood $product) {
        return $this->seoService->getProductSEO($product);
    }

    public function getAllProductDetails(PetFood $product) {
        if (!$product) {
            return $product;
        }

        /* @var PetFoodService $catfoodService */
        $catfoodService = $this->getContainer()->get('catfood');

        $shopService = $this->getContainer()->get('shop.service');
        $shop = $shopService->getAll($product->getId());
        $product->addExtraData('shopUrls', $shop);

        return $catfoodService->updateExtendedProductDetails(
            $product,
            $this->getParameter('amazon.purchase.url.template'),
            $this->getContainer()->get('catfood.analysis'),
            $this->getContainer()->get('analysis.access'));
        
    }

    /**
     * Render the product details for a specific product
     *
     * @param PetFood|null $product
     */
    protected function renderProduct(PetFood $product = null, $template=null) {
        if (!$product) {
            $this->app->notFound();
        }

        $productData = $this->getRenderProductTemplateData($product);


        if (!$template) {
            $template = "product.html.twig";
        }

        $this->render($template, $productData);
    }

    public function getRenderProductTemplateData(PetFood $product = null) {
        if (!$product) {
            return null;
        }

        $product = $this->getAllProductDetails($product);

        if (!$product) {
            $this->app->notFound();
        }

        /* @var AnalysisWrapper $analysisWrapper */
        $analysisWrapper = $this->getContainer()->get('analysis.access');

        $helper = $this->getContainer()->get('catfood.url');

        $seo = $this->getProductSEO($product);
        $brandInfo = $this->get('brand.info')->getBrandInfo($product->getBrand());

        $amazonQuery = $this->getAmazonSearchQuery($product);

        $shareText = "#CatFoodDB review: " . $product->getDisplayName();


        $productData = [
            'product' => $product,
            'productStats' => $analysisWrapper->getProductAnalysis($product),
            'seo' => $seo,
            'brand'=> $brandInfo,
            'related' => $this->getRelatedProducts($product),
            'amazonQuery' => $amazonQuery,
            'reviewNavClass' => 'active',
            'shareText'  => urlencode($shareText),
            'shareImage' => urlencode($helper->imageUrl($product)),
            'debug' => $this->getParameter('app.debug'),
            'calorieChart' => $this->getCalorieChart($product)
        ];
        

        return $productData;

    }

    protected function getCalorieChart(PetFood $product) {
        $calories = $product->getCaloriesPer100g();

        $title = "CalorieBreakdown";
        $lava = $this->get('lava');
        $data = $lava->DataTable();
        $data->addStringColumn('Col1')
             ->addNumberColumn('Col2');
        $data->addRows([
            ['Calories From Carbs', $calories['carbohydrates']],
            ['Calories From Fat', $calories['fat']],
            ['Calories From Protein', $calories['protein']]
        ]);

        $lava->PieChart($title, $data, [
            'chartArea' => [
                'left' => 0,
                'top' => 0,
                'width' => '100%',
                'height' => '92%',

            ],
            'legend' => [
                'position' => 'right'
            ],
            'slices' => [
                ['color'=>'#A94441', 'textStyle' => ['fontSize' => '14'] ],
                ['color'=>'#8a6d3b', 'textStyle' => ['fontSize' => '14'] ],
                ['color'=>'#3e3354', 'textStyle' => ['fontSize' => '14'] ],
            ],
            'fontName' => "Maven Pro"
        ]);


        return $title;



    }

    protected function getPriceDisplay(PetFood $product) {

        $asin = $product->getAsin();
        $display = null;
        if ($asin) {
            $lookup = $this->get('amazon.lookup');
            $price = $lookup->lookupPrice($asin);
            if (!empty($price['size'])) {
                $formattedPrice = sprintf("%s %s / %s", $price['price'], $price['currency'], $price['size']);
                $display = $formattedPrice;
            }
        }
        

        return $display;

    }

    /**
     * Get Related for products. For now it's just those of the same brand and type (wet vs dry)
     *
     * @param PetFood $catFood
     *
     * @return array
     */
    public function getRelatedProducts(PetFood $catFood) {
        $brand = $catFood->getBrand();
        $related = $this->catFoodService->getByBrand($brand);

        $related = array_filter($related, function(PetFood $item) use ($catFood) {
            if ($catFood->getId() == $item->getId()) {
                return false; //self is not related
            }
            if ($catFood->getIsWetFood() && $item->getIsWetFood()) {
                return true;
            }
            if ($catFood->getIsDryFood() && $item->getIsDryFood()) {
                return true;
            }
            return false;
        });

        return $related;
    }
}
