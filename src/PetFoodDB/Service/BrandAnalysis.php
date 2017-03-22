<?php


namespace PetFoodDB\Service;


class BrandAnalysis extends BaseService
{

    const ANALYSIS_DB_NAME = 'brands';

    protected $catFoodService;
    protected $analysisWrapper;
    protected $analysisService;

    
    public function __construct(\NotORM $db, PetFoodService $catfoodService, NewAnalysisService $analysisService, AnalysisWrapper $analysisWrapper)
    {
        parent::__construct($db);
        $this->catFoodService = $catfoodService;
        $this->analysisService = $analysisService;
        $this->analysisWrapper = $analysisWrapper;
    }

    public function getBrandData($brand) {
        $rv = $this->db->brands->where("brand", $brand)->fetch();
        if ($rv) {
            return iterator_to_array($rv);
        } else {
            return [];
        }
    }

    public function getAllData() {
        $rows = [];
        $result = $this->db->brands;
        foreach ($result as $row) {
            $rows[] = iterator_to_array($row);
        }

        return $rows;
    }


    public function calculateBrandAnalysis(callable $brandProgress = null)
    {

        /* @var \PetFoodDB\Service\PetFoodService $catfoodService */
        $catfoodService = $this->catFoodService;
        $analysisService = $this->analysisService;
        $analysis = $this->analysisWrapper;
        $brands = $this->catFoodService->getBrands();

        $wetRows = [];
        $dryRows = [];
        $combinedRows = [];

        foreach ($brands as $brand) {

            $products = $catfoodService->getByBrand($brand['name']);

            $wet = [];
            $dry = [];
            foreach ($products as $product) {
                $stats = $analysis->getProductAnalysis($product);
                $product->addExtraData('stats', $stats);
                $product = $catfoodService->updateExtendedProductDetails($product, "", $analysisService, $analysis);
                if ($product->getIsWetFood()) {
                    $wet[] = $product;
                } else {
                    $dry[] = $product;
                }
            }

            $products = array_merge($wet, $dry);
            $wetRating = $catfoodService->calculateAverageRating($wet);
            $dryRating = $catfoodService->calculateAverageRating($dry);
            $combinedRating = $catfoodService->calculateAverageRating($products);

            $combinedRows[] = [
                'brand' => $brand['brand'],
                'num_total' => count($products),
                'avg_nut_rating' => $this->formatValue($combinedRating['nutrition_rating']),
                'avg_ing_rating' => $this->formatValue($combinedRating['ingredients_rating']),
                'avg_total_score' => $this->formatValue($combinedRating['total_rating']),
            ];

            if (count($wet)) {
                $wetRow =
                    [
                        'brand' => $brand['brand'],
                        'num_wet' => count($wet),
                        'wet_avg_nut_rating' => $this->formatValue($wetRating['nutrition_rating']),
                        'wet_avg_ing_rating' => $this->formatValue($wetRating['ingredients_rating']),
                        'wet_avg_total_score' => $this->formatValue($wetRating['total_rating']),
                    ];

                $wetRows[] = $wetRow;

            }

            if (count($dry)) {

                $dryRow = [
                    'brand' => $brand['brand'],
                    'num_dry' => count($dry),
                    'dry_avg_nut_rating' => $this->formatValue($dryRating['nutrition_rating']),
                    'dry_avg_ing_rating' => $this->formatValue($dryRating['ingredients_rating']),
                    'dry_avg_total_score' => $this->formatValue($dryRating['total_rating'])
                ];

                $dryRows[] = $dryRow;
            }

            if ($brandProgress) {
                call_user_func($brandProgress);
            }

        }

        $callable = $this->buildRowSortFunction('wet_avg_total_score');
        usort($wetRows, $callable);

        $callable = $this->buildRowSortFunction('dry_avg_total_score');
        usort($dryRows, $callable);

        $callable = $this->buildRowSortFunction('avg_total_score');
        usort($combinedRows, $callable);

        $wetRows = $this->addNumbering($wetRows);
        $dryRows = $this->addNumbering($dryRows);
        $combinedRows = $this->addNumbering($combinedRows);

        //need to merge the data
        $keys = array_map(function($b) { return $b['brand']; }, $brands);
        $data = array_fill(0, count($keys), []);
        $mergedData = array_combine($keys, $data);


        $headers = [];
        foreach ($wetRows as $wetRow) {
            $brand = $wetRow['brand'];
            $existingData = $mergedData[$brand];
            $wetRow['wet_rank'] = $wetRow['id'];
            $wetRow['wet_brand_count'] = count($wetRows);
            unset($wetRow['id']);
            $mergedData[$brand] = array_merge($existingData, $wetRow);

        }


        foreach ($dryRows as $dryRow) {
            $brand = $dryRow['brand'];
            $existingData = $mergedData[$brand];
            $dryRow['dry_rank'] = $dryRow['id'];
            $dryRow['dry_brand_count'] = count($dryRows);
            unset($dryRow['id']);
            $mergedData[$brand] = array_merge($existingData, $dryRow);

        }

        foreach ($combinedRows as $row) {
            $brand = $row['brand'];
            $existingData = $mergedData[$brand];
            $row['rank'] = $row['id'];
            $row['brand_count'] = count($combinedRows);
            unset($row['id']);
            $mergedData[$brand] = array_merge($existingData, $row);

        }

        foreach ($mergedData as $brand=>$brandData) {
            $keys = array_keys($brandData);
            if (count($keys) > count($headers)) {
                $headers = $keys;
            }
        }

        $displayData = [];
        foreach ($mergedData as $brand=>$initRow) {
            $row = [];
            foreach ($headers as $header) {
                if (isset($initRow[$header])) {
                    $row[] = $initRow[$header];
                } else {
                    $row[] = "-";
                }
            }
            $displayData[] = $row;
        }


        return [
            'headers' => $headers,
            'data' => $displayData
        ];
        
    }

    protected function formatValue($value) {
        return $value ? sprintf("%0.02f", $value) : "----";
    }

    /**
     * @param string $key Key to sort array on
     * @return \Closure
     */
    protected function buildRowSortFunction($key) {
        $ftn = function($rowA, $rowB) use ($key) {
            $scoreA = $rowA[$key];
            $scoreB = $rowB[$key];

            return ($scoreA < $scoreB) ? 1 : -1;
        };

        return $ftn;
    }

    protected function addNumbering(array $data) {
        $newData = [];
        foreach ($data as $i=>$row) {
            $newRow = array_merge(['id' => $i+1], $row);
            $newData[] = $newRow;
        }

        return $newData;
    }


    public function initDB() {
        //1: drop existing db
        $this->dropTable();

        //2: recreate db
        $this->createTable();
    }

    protected function dropTable() {
        $table = self::ANALYSIS_DB_NAME;
        $pdo = $this->getPDO();

        $sql = "Drop table if exists $table";
        $pdo->query($sql);
    }

    protected function createTable() {
        $table = self::ANALYSIS_DB_NAME;
        $pdo = $this->getPDO();

        $sql = <<<EOT
        CREATE TABLE "$table" ("brand" VARCHAR PRIMARY KEY  NOT NULL  UNIQUE , "num_wet" INTEGER, "wet_avg_nut_rating" DOUBLE, "wet_avg_ing_rating" DOUBLE, "wet_avg_total_score" DOUBLE, "wet_rank" INTEGER, "wet_brand_count" INTEGER, "num_dry" INTEGER, "dry_avg_nut_rating" DOUBLE, "dry_avg_ing_rating" DOUBLE, "dry_avg_total_score" DOUBLE, "dry_rank" INTEGER, "dry_brand_count" INTEGER, "num_total" INTEGER, "avg_nut_rating" DOUBLE, "avg_ing_rating" DOUBLE, "avg_total_score" DOUBLE, "rank" INTEGER, "brand_count" INTEGER);
EOT;
        $sql = trim($sql);
        $sql = trim($sql);
        $pdo->query($sql);

    }

    public function updateDB(callable $progress = null) {

        $this->initDB();

        $data = $this->calculateBrandAnalysis($progress);
        $data = $this->cleanDataForDB($data);

        foreach ($data as $row) {
            $this->db->brands->insert($row);
        }



    }

    protected function cleanDataForDB($brandAnalysis) {

        $headers = $brandAnalysis['headers'];
        $data = $brandAnalysis['data'];

        //now need to remove all non-numeric values
        $cleanData = [];
        foreach ($data as $row) {
            $newRow = [];
            foreach ($row as $i => $value) {
                if ($i > 0 && !is_numeric($value)) {
                    $newRow[$headers[$i]] = null;
                } else {
                    $newRow[$headers[$i]] = $value;
                }
            }

            $cleanData[] = $newRow;
        }

        return $cleanData;
    }
}
