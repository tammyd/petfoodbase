<?php


namespace PetFoodDB\Controller\Admin;

use PetFoodDB\Controller\PageController;
use PetFoodDB\Service\ShopService;
use PetFoodDB\Traits\ArrayTrait;
use PetFoodDB\Controller\BaseController;
use PetFoodDB\Model\PetFood;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;


class AdminController extends PageController
{
    protected $insertPath = "/admin/insert";
    protected $updatePath = "/admin/update";

    protected $iframeNonSupportedBrands = [
        'by nature',
        'castor & pollux',
        'purina cat chow',
        'evo',
        'fancy feast',
        'friskies',
        'iams',
        'merrick',
        'nutro',
        'petcurean',
        'purina pro plan',
        'royal canin',
        'purina beyond',
        'ziwipeak',
        'chicken soup for the soul',
        'purina one',
        "newmans's own",
        'whole earth farms',
        'muse'
    ];

    protected function validateCredentials() {
        if (!$this->isAdmin()) {
            $this->app->notFound();
        }
    }

    public function phpInfoAction() {
        $this->validateCredentials();

        phpinfo();
    }

    /**
     * Action for the product list page
     */
    public function listAction()
    {
        $this->validateCredentials();
        $all = $this->catFoodService->getAll();

        $productsByBrand = $this->getAllProductsByBrand();
        $totalRows = count($productsByBrand) + count($all);


        $this->render('list.html.twig', ['products'=>$productsByBrand, 'totalRows' => $totalRows, 'productNavClass'=> 'active']);
    }

    public function productAction($id) {
        $this->validateCredentials();
        $controller = $this->get('product.controller');


        /* @var PetFood $product */
        $product = $this->catFoodService->getById($id);
        if (!$product) {
            $this->app->notFound();
        }


        $data = $controller->getRenderProductTemplateData($product);
        $data['breakdowns'] = $product->getPercentages();

        $cloner = new VarCloner();
        $dumper = new HtmlDumper();
        $output = fopen('php://memory', 'r+b');

        $dumper->dump($cloner->cloneVar($data), $output);
        $output = stream_get_contents($output, -1, 0);
        $this->render('admin/empty.html.twig', ['content' => $output]);
    }


    public function imagesAction() {
        $this->validateCredentials();

        $start = (int) $this->getRequest()->params('start');
        
        if (!$start) {
            $start = 1;
        }

        $catfoodService = $this->getContainer()->get('catfood');
        $dbConnection = $catfoodService->getDb()->getConnection();

        $sql = "select * from catfood where id >= $start order by id limit 100";
        $result = $dbConnection->query($sql);

        $products = [];
        $maxId = 0;
        foreach ($result as $i => $row) {
            $product = new PetFood($row);
            $products[] = $product;
            $maxId = max($maxId, $product->getId());
        }

        $next = "/admin/images?start=" . ($maxId + 1);
        $this->render('debug/images.html.twig', ['products' => $products, 'next' => $next]);

    }
    

    /**
     * Action for the insert catfood form
     */
    public function getInsertFormAction()
    {
        $this->validateCredentials();
        $defaultVars = $this->app->request->get();

        unset($defaultVars['id']);

        $this->render('admin.html.twig', ['defaults' => $defaultVars, 'posturl' => '/admin/insert/process']);
    }

    /**
     * Action for the update catfood form
     * @param $id
     */
    public function getUpdateFormAction($id)
    {
        $this->validateCredentials();
        $service = $this->getContainer()->get('catfood');
        $catFood = $service->getById($id);
        if (!$catFood) {
            $this->app->notFound();
        }
        $catFood->setPurchaseAsinTemplate($this->getContainer()->get('config')['amazon.purchase.url.template']);
        $vars = $catFood->dbModel();

        $showIframe = !in_array(strtolower($catFood->getBrand()), $this->iframeNonSupportedBrands);

        $shop = $this->getShopData($id);
        if ($shop) {
            $vars = array_merge($vars, $shop);
        };

        $this->render('admin.html.twig', [
            'defaults' => $vars,
            'catfood'=> $catFood,
            'posturl' => '/admin/update/process',
            'showIframe' => false
        ]);
    }


    /**
     * Action from the insert form post
     */
    public function submitInsertAction()
    {
        $this->validateCredentials();
        $service = $this->getContainer()->get('catfood');
        $submittedVars = $this->app->request->post();

        $catFood = $this->buildCatFoodFromInput($submittedVars, 'update');

        if (!$catFood) {
            $url = $this->insertPath . '?' . http_build_query($submittedVars);
            $this->app->redirect($url);
        }


        $row = $service->insert($catFood);

        $id = $service->getLastId();
        $this->updateSubmittedShopData($id, $submittedVars);

        
        $this->app->flash('insert', "Insert successful!");
        $url = '/admin/insert?brand=' . $catFood->getBrand();
        $this->app->redirect($url);

    }

    /**
     * action from the update form post
     */
    public function submitUpdateAction()
    {
        $this->validateCredentials();
        $service = $this->getContainer()->get('catfood');
        $submittedVars = $this->app->request->post();
        $id = intval($submittedVars['id']);

        $catFood = $this->buildCatFoodFromInput($submittedVars);

        $catFood->setId($id);

        if (!is_numeric($id)) {
            $this->app->flash('error', "Invalid update - empty id");
        }

        if (!$catFood) {
            $url = $this->updatePath . '?' . http_build_query($submittedVars);
            $this->app->redirect($url);
        }

        $service->update($catFood);
        
        $this->updateSubmittedShopData($id, $submittedVars);
        
        
        $this->app->flash('update', "Update successful!");
        $this->app->redirect('/admin/update/'.$catFood->getId());
    }

    protected function getShopData($id) {
        /* @var ShopService $service */
        $service = $this->get('shop.service');
        return $service->getAll($id);
    }
    
    protected function updateSubmittedShopData($id, $submittedVars) {

        /* @var ShopService $service */
        $service = $this->get('shop.service');
        $chewy = $this->getArrayValue($submittedVars, 'chewy');
        $petsmart = $this->getArrayValue($submittedVars, 'petsmart');

        //if ($chewy) {
            $service->updateChewy($id, $chewy);
        //}

        if ($petsmart) {
            $service->updatePetsmart($id, $petsmart);
        }


    }

    /**
     * @param array $input
     * @param string $action
     * @return PetFood
     */
    protected function buildCatFoodFromInput(array $input, $action="insert") {
        $isError = false;
        $textFields = ['brand', 'flavor', 'source', 'ingredients'];
        $numberFields = ['protein', 'fat', 'fibre', 'moisture', 'ash'];
        $optionalFields = ['asin', 'imageUrl'];
        $data = [];
        foreach ($textFields as $field) {
            if (!isset($input[$field]) || $this->validateTextField($input[$field]) === false) {
                $this->app->flash('error', "Invalid text $action - $field was empty");
                $isError = true;
            }

            //do some clean up
            if ($field == 'ingredients') {
                $ing = $this->validateTextField($input[$field]);
                $ing = trim($ing);
                $ing = preg_replace("/(.+)\.$/", "$1", $ing);
                $ing = preg_replace('!\s+!', ' ', $ing);
                $data[$field] = $ing;
            } else {
                $data[$field] = $this->validateTextField($input[$field]);
            }
        }
        foreach ($numberFields as $field) {
            if (!isset($input[$field]) || $this->validateNumberField($input[$field]) === false) {
                $this->app->flash('error', "Invalid number $action - $field was empty");
                $isError = true;
            }
            $data[$field] = $this->validateNumberField($input[$field]);
        }
        foreach ($optionalFields as $field) {
            if (isset($input[$field]) && $input[$field]) {
                $data[$field] = $this->validateTextField($input[$field]);
            } else {
                $data[$field] = null;
            }
        }

        $data['discontinued'] =  (isset($input['discontinued']) && $input['discontinued'] == 'on')  ? 1 : 0;
        $data['raw'] =  (isset($input['raw']) && $input['raw'] == 'on')  ? 1 : 0;
        $data['baby'] =  (isset($input['baby']) && $input['baby'] == 'on')  ? 1 : 0;
        $data['veterinary'] =  (isset($input['veterinary']) && $input['veterinary'] == 'on')  ? 1 : 0;
        

        if ($action == 'insert') {
            $data['updated'] = new \DateTime();
        }
        
        if (!$isError) {
            $catfood = new PetFood($data);
            return $catfood;
        }

    }

    /**
     * @param $value
     * @return bool
     */
    protected function validateNumberField($value) {
        if (!is_numeric($value)) {
            return false;
        }
        if ($value < 0 || $value > 100) {
            return false;
        }

        return floatval($value);
    }

    /**
     * @param $value
     * @return bool
     */
    protected function validateTextField($value) {
        if (strlen($value) < 1) {
            return false;
        }

        return $value;
    }



}