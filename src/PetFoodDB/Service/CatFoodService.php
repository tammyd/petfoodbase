<?php

namespace PetFoodDB\Service;

use PetFoodDB\Model\CatFood;
use PetFoodDB\Traits\StringHelperTrait;

class CatFoodService extends BaseService
{

    use StringHelperTrait;

    static $slugMap;

    public function getById($id)
    {
        $data = $this->db->catfood[$id];

        return $data ? new CatFood(iterator_to_array($data)) : null;

    }

    public function getByBrandSlug($brand, $slug) {
        $ids = [];
        
        $flavor = mb_strtolower($slug);

        $brand = strtolower($brand);
        $data = $this->db->catfood_search()
            ->select('id')
            ->where('brand', $brand)
            ->where('flavor', $flavor);

        $this->getLogger()->debug("QUERY: " . implode(", ", [$data->__toString(), $brand, $flavor]));
        foreach ($data as $row) {
            $ids[] = $row['id'];
        }

        $items = $this->getByIds($ids);

        return isset($items[0]) ? $items[0] : null;
    }

    public function getByBrand($brand) {
        $brand = strtolower($brand);
        $ids = [];
        $data = $this->db->catfood_search()
            ->select('id')
            ->where('brand', $brand);
        foreach ($data as $row) {
            $ids[] = $row['id'];
        }

        return $this->getByIds($ids);
    }

    public function getAll() {
        $data = $this->db->catfood()
            ->select('*');
        return $this->convertResultToCatfood($data);

    }


    public function getByIds(array $ids)
    {
        $data = $this->db->catfood()
            ->select('*')
            ->where('id', $ids);

        return $this->convertResultToCatfood($data);

    }

    public function convertResultToCatfood(\NotORM_Result $result)
    {
        $catFood = [];
        foreach ($result as $row) {
            $catFood[] = new CatFood(iterator_to_array($row));
        }

        return $catFood;
    }

    public function textSearch($string, $brandFilter = null)
    {
        $string = $this->cleanUpQuery($string);
        $ids = [];

        if (!$string && empty($brandFilter)) {
            return [];
        }

        $data = $this->db->catfood_search()->select('id');
        if ($string) {
            $data = $data->where('catfood_search match ?', "$string");
        }
        if ($brandFilter) {
            $data = $data->where('brand', $brandFilter);
        }
        $data = $data->group('brand, flavor, ingredients');

        $this->getLogger()->debug("QUERY: " . implode(", ", [$data->__toString(), $string]));
        foreach ($data as $row) {
            $ids[] = $row['id'];
        }

        return $this->getByIds($ids);
    }

    protected function cleanUpQuery($string)
    {
        //extended full text search requires all caps for the operators
        $string = str_replace(' or ', ' OR ', $string); //or should be all caps
        $string = str_replace([' and ', ' AND '], ' ', $string); //and is implied
        $string = str_replace([' not ', ' NOT '], ' -', $string); //replace NOT with - for sqlite3 standard fts query

        $string = trim($string);
        if ($this->startsWith($string, "-")) {
            //fake a search that does a not search against all
            $string = "catfood $string";
        }

        //if string has an odd number of quotes, add to the end
        foreach (["'", '"'] as $quote) {
            $count = substr_count($string, $quote);
            if ($count % 2 != 0 && strpos($string, $quote) === 0) {
                $string .= $quote;
            }
        }

        return $string;
    }

    public function getStats()
    {
        $stats = [
            'total' => $this->getNumberRecords(),
            'wet' => $this->getNumberWetRecords(),
            'dry' => $this->getNumberDryRecords(),
            'brandCount' => $this->getNumberBrands()
        ];

        return $stats;

    }

    protected function getNumberBrands() {
        return count($this->getBrands());
    }


    public function getBrands()
    {

        $brands = [];
        //SELECT catfood.brand as name, catfood_search.brand as brand FROM catfood_search LEFT JOIN catfood ON catfood_search.id = catfood.id WHERE (catfood.id = catfood_search.id) group by catfood.brand
        $result = $this->db->catfood_search()
            ->select('catfood.brand as name, catfood_search.brand as brand')
            ->where('catfood:id = catfood_search.id')
            ->group('catfood.brand')
            ->order('catfood.brand COLLATE NOCASE ASC');
        foreach ($result as $i=>$row) {
            $brands[] = ['index'=>$i, 'name'=>$row['name'], 'brand'=>$row['brand']];
        }

        return $brands;
    }

    public function getNumberRecords()
    {

        //We want the # of unique cat products. Due to the nature of the scraping, a few products have multiple pages, resulting in multiple records.
        //This fixes that, but we had to use a raw query to get the data.
        $result = $this->getDb()->getConnection()->query("select count(id) as total from catfood where id in (select min(id) from catfood group by brand,flavor,ingredients,protein,fat,moisture,ash)");

        return $result->fetch()['total'];

    }

    public function getNumberWetRecords() {
        return count($this->db->catfood->where("moisture > ? ", CatFood::WET_DRY_PERCENT));
    }
    public function getNumberDryRecords() {
        return count($this->db->catfood->where("moisture <= ? ", CatFood::WET_DRY_PERCENT));
    }

    public function insert(CatFood $catfood)
    {

        return $this->db->catfood->insert($catfood->dbModel());
    }

    public function getLastId() {
       return $this->db->catfood->insert_id();
    }

    public function update(CatFood $catfood)
    {
        $dbModel = $catfood->dbModel();
        
        return $this->db->catfood[$dbModel['id']]->update($dbModel);

    }

    public function getLastDBUpdate() {

        $result = $this->db->catfood()->select("max(updated) as last_updated");
        return $result->fetch()['last_updated'];

    }

    public function getRecentlyUpdatedBrands() {

        $result = $this->getDb()->getConnection()
            ->query("SELECT distinct(catfood_search.brand) as brand, 
catfood.brand as name from catfood_search, catfood where 
catfood_search.id = catfood.id order by catfood.updated DESC");


        $brands = [];
        foreach ($result as $i=>$row) {
            $brands[] = ['index'=>$i, 'name'=>$row['name'], 'brand'=>$row['brand']];
        }

        return $brands;

    }

    public function getPopularBrands() {

        $brands = [
            'simply nourish',
            'wellness',
            'weruva',
            'orijen',
            'blue buffalo',
            'nutro',
            'merrick',
            'whiskas',
            'natural balance',
            'fancy feast',
            'royal canin',
            'purina beyond'
        ];

        return $this->getSpecificBrands($brands);

    }

    public function getSpecificBrands(array $brandNames)
    {

        $brands = [];
        //SELECT catfood.brand as name, catfood_search.brand as brand FROM catfood_search LEFT JOIN catfood ON catfood_search.id = catfood.id WHERE (catfood.id = catfood_search.id) group by catfood.brand
        $result = $this->db->catfood_search()
            ->select('catfood.brand as name, catfood_search.brand as brand')
            ->where('catfood.id = catfood_search.id')
            ->where('catfood_search.brand', $brandNames)
            ->group('catfood.brand')
            ->order('catfood.brand COLLATE NOCASE ASC');
        foreach ($result as $i=>$row) {
            $brands[] = ['index'=>$i, 'name'=>$row['name'], 'brand'=>$row['brand']];
        }

        return $brands;
    }


    public function updateExtendedProductDetails(CatFood $product, $amazonTemplate, NewAnalysisService $analysisService, AnalysisWrapper $analysisWrapper) {
        if (!$product) {
            return $product;
        }

        $product->setPurchaseAsinTemplate($amazonTemplate);
        $ingredientService = $analysisService->getIngredientService();

        $stats = $analysisWrapper->getProductAnalysis($product);

        $allergenData = $this->buildAllergenData($product, $analysisService);

        $product->addExtraData('stats', $stats);
        $product->addExtraData('allergenData', $allergenData);

        $proteins = [];
        $byproducts = [];
        $fillers = [];
        AnalyzeIngredients::getTopIngredientsByType($product, $proteins, $byproducts, $fillers);

        $ingredientAnalysis = $ingredientService::analyzeIngredients($product);

        $preservatives = AnalyzeIngredients::hasUndesierablePreservative($product);
        $additives = AnalyzeIngredients::hasQuestionableAdditive($product);

        $topIsMoisture = AnalyzeIngredients::isMoistureSource($product, 1) ? true: false;
        $product->addExtraData('proteins', $proteins);
        $product->addExtraData('byproducts', $byproducts);
        $product->addExtraData('fillers' , $fillers);
        $product->addExtraData('analysis', $ingredientAnalysis);
        $product->addExtraData('topIsMoisture', $topIsMoisture);
        $product->addExtraData('preservatives', $preservatives);
        $product->addExtraData('additives', $additives);
        $product->addExtraData('avgPrice', $this->getAvgPrice($product));

        return $product;
    }

    protected function getAvgPrice(CatFood $product) {
        $data =  $this->db->prices[$product->getId()];
        if ($data) {
            $arr = iterator_to_array($data);
            return $arr['avg'];
        }
        return false;
    }

    protected function getChewyUrl(CatFood $product) {
        $data =  $this->db->prices[$product->getId()];
        if ($data) {
            $arr = iterator_to_array($data);
            return $arr['avg'];
        }
        return false;
    }

    protected function buildAllergenData(CatFood $product, NewAnalysisService $analysisService) {
        $allergens = $analysisService->getIngredientService()->containsAllergens($product);

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

    public function calculateAverageRating(array $products) {

        $nutScore = 0;
        $ingScore = 0;
        $avgNutScore = 0;
        $avgIngScore = 0;
        $totalScore = 0;
        foreach ($products as $product) {
            $nutScore += $product->getExtraData('stats')['nutrition_rating'];
            $ingScore += $product->getExtraData('stats')['ingredients_rating'];
        }

        if ($products) {
            $avgNutScore = $nutScore / count($products);
            $avgIngScore = $ingScore / count($products);
            $totalScore = $avgNutScore + $avgIngScore;
        }

        return [
            'nutrition_rating' => $avgNutScore,
            'ingredients_rating' => $avgIngScore,
            'total_rating' => $totalScore
        ];

    }

    public function productContainsIngredient(CatFood $product, $ing) {
        $ingredients =  array_map('strtolower', array_map('trim', explode(",", $product->getIngredients())));
        return in_array($ing, $ingredients);
    }


}
