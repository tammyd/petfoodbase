<?php

namespace PetFoodDB\Controller;

use PetFoodDB\Model\BaseList;

class PetFoodController extends BaseController
{

    public function __construct(\Slim\Slim &$app)
    {
        parent::__construct($app);
        $this->getResponse()->headers->set('Content-Type', 'application/json');
    }

    public function getByIdAction($id)
    {
        if (!$this->handleAuth()) {
            return;
        }

        $data = $this->get('catfood')->getById($id);
        

        if (!is_null($data)) {
            $this->prepListResponse($data);
        } else {
            $this->is404();
        }
    }

    protected function parseBrandFilter()
    {
        $brands = null;
        $param = $this->getRequest()->params('brands');
        if ($param) {
            $brands = array_map('trim', explode(",", $param));
        }

        return $brands;
    }

    public function searchAction($search)
    {

        if (!$this->handleAuth()) {
            return;
        }

        $brandFilter = $this->parseBrandFilter();

        $data = $this->get('catfood')->textSearch($search, $brandFilter);

        //remove discontinued items
        $data = array_filter($data, function($x)  {
            if ($x instanceof \PetFoodDB\Model\PetFood) {
                return !$x->getDiscontinued();
            }
            return true;
        });


        //sort search results by moisture
        $data = $this->get('catfood')->sortPetFoodBy($data, 'moisture', true);

        if (!is_null($data)) {
            $this->prepListResponse($data);
        } else {
            $this->is404();
        }
    }

    public function brandsAction()
    {
        if (!$this->handleAuth()) {
            return;
        }

        $data = $this->get('catfood')->getBrands();
        if (!is_null($data)) {
            $this->prepListResponse($data);
        } else {
            $this->is404();
        }
    }

    public function statsAction()
    {
        if (!$this->handleAuth()) {
            return;
        }

        $stats = $data = $this->get('catfood')->getStats();
        $stats['admin'] = $this->isAdmin();

        $this->getResponse()->setBody($this->get('catfood.serializer')->serialize($stats, 'json'));
    }

    protected function prepAmazon(BaseList $catfoodList)
    {
        foreach ($catfoodList as $catFood) {
            if ($catFood instanceof \PetFoodDB\Model\PetFood) {
                $catFood->setPurchaseAsinTemplate($this->getParameter('amazon.purchase.url.template'));
            }
        }

        return $catfoodList;
    }

    protected function prepChewy(BaseList $catfoodList)
    {
        $shopService = $this->getContainer()->get('shop.service');

        foreach ($catfoodList as $catFood) {
            if ($catFood instanceof \PetFoodDB\Model\PetFood) {
                $shop = $shopService->getAll($catFood->getId());
                $catFood->addExtraData('shop', $shop);
            }
        }

        return $catfoodList;
    }


    protected function prepListResponse($data, $retry=false)
    {

        if (!is_null($data) && !is_array($data)) {
            $data = [$data];
        }

        $list = new BaseList($data);
        $list = $this->prepAmazon($list);
        $list = $this->prepChewy($list);
        $list = $this->prepRatings($list);


        $maxAge = 7*24*60*60;
        $this->getResponse()->headers()->set('Cache-Control', "max-age=$maxAge, public");
        $this->getResponse()->headers()->set('CDN-Cache-Control', "max-age=$maxAge, public");
        try {
            $this->getResponse()->setBody($this->get('catfood.serializer')->serialize($list, 'json'));
        } catch (\UnexpectedValueException $e) {
            if ($retry) {
                $this->getLogger()->err("ERROR on prepListResponse retry: " . $e->getMessage());
                return null;
            }

            $deadlyIds = [];
            //probably a json encoding error. Remove the items that have errors and try again with the rest.
            $items = $list->getItems();
            foreach ($items as $item) {
                try {
                    $enc = $this->get('catfood.serializer')->serialize($item, 'json');
                } catch (\Exception $e) {
                    $this->getLogger()->err("ERROR SERIALIZING Item "  . $item->getId());
                    $deadlyIds[] = $item->getId();
                }

            }
            $newItems = array_filter($items, function($x) use ($deadlyIds) {
                return !in_array($x->getId(), $deadlyIds);
            });

            $list = $list->setItems($newItems);

            return $this->prepListResponse($list, true);
        }
    }

    protected function prepRatings(BaseList $catfoodList)
    {

        $anaylsisService = $this->get('analysis.access');
        foreach ($catfoodList as $catFood) {
            if ($catFood instanceof \PetFoodDB\Model\PetFood) {
                $analysis = $anaylsisService->getProductAnalysis($catFood);
                $catFood->addExtraData('nutritionScore', (int)$analysis['nutrition_rating']);
                $catFood->addExtraData('ingredientScore', (int)$analysis['ingredients_rating']);
                $catFood->addExtraData('totalScore', $analysis['nutrition_rating'] + $analysis['ingredients_rating']);
            }
        }

        return $catfoodList;
    }

    protected function handleAuth()
    {
        if (!$this->isAuthenticationValid()) {
            $this->getResponse()->setStatus(400);
            $this->getResponse()->setBody(json_encode(['error'=>400]));

            return false;
        }

        return true;

    }

    protected function isAuthenticationValid()
    {
        $authService = $this->get('api.auth');
        if (!$authService->isEnabled()) {
            return true; //no auth, all is allowed
        }
        $sessionKey = 'authKey';
        if (isset($_SESSION[$sessionKey])) {
            $authKey = $_SESSION[$sessionKey];

            if ($authService->isValidAuthKey($authKey)) {
                return true;
            }
        }

        //check for super secret header
        return ($this->isAdmin());
    }

}
