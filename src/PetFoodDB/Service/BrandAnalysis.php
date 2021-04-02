<?php


namespace PetFoodDB\Service;


use PetFoodDB\Traits\MathTrait;

class BrandAnalysis extends BaseService
{

    const ANALYSIS_DB_NAME = 'brands';

    const SIG_ABOVE_AVG = 5;
    const ABOVE_AVG = 4;
    const AVG = 3;
    const BELOW_AVG = 2;
    const SIG_BELOW_AVG = 1;

    protected $catFoodService;
    protected $analysisWrapper;
    protected $analysisService;

    use MathTrait;

    
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

    public function hasAnyPurchaseInfo($brand, $type=null) {
        $pdo = $this->getPDO();

        switch ($type) {
            case 'wet':
                $sql = "SELECT * from (catfood left join shop on (catfood.id = shop.id)) "
                    . "where lower(catfood.brand) = :brand and (length(chewy) > 0 or length(asin) > 0) "
                    . "and moisture >20 and discontinued = 0";
                break;
            case 'dry':
                $sql = "SELECT * from (catfood left join shop on (catfood.id = shop.id)) "
                    . "where lower(catfood.brand) = :brand and (length(chewy) > 0 or length(asin) > 0) "
                    . "and moisture <=20 and discontinued = 0";
                break;
            default:
                $sql = "SELECT * from (catfood left join shop on (catfood.id = shop.id)) "
                    . "where lower(catfood.brand) = :brand and (length(chewy) > 0 or length(asin) > 0) "
                    . " and discontinued = 0";
        }

        $sth = $pdo->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute([':brand' => $brand]);
        $result = $sth->fetchAll(\PDO::FETCH_COLUMN);

        $result = (int)array_pop($result) ? true: false;
        return $result;
    }


    public function getAllData() {
        $rows = [];
        $result = $this->db->brands;
        foreach ($result as $row) {
            $rows[] = iterator_to_array($row);
        }

        return $rows;
    }

    public function getLastUpdated($brand) {
        /* @var \PetFoodDB\Service\PetFoodService $catfoodService */
        $catfoodService = $this->catFoodService;
        $products = $catfoodService->getByBrand($brand);
        $dates = array_map(function($product) {
            return $product->getDiscontinued() ? null : $product->getUpdated();
        }, $products);
        $dates = array_filter($dates);
        $min = min($dates);
        
        return $min;
    }


    public function rateBrand ($brand) {
        $brandData = $this->getBrandData($brand);

        if (empty($brandData)) {
            $rv = [
                'overallRating' => 0,
                'dryRating' => 0,
                'wetRating' => 0
            ];
            return $rv;
        }
        $maxOverall = $brandData['brand_count'];
        $overallRank = $brandData['rank'];

        $overallRating = 0;
        $dryRating = null;
        $wetRating = null;

        //get overall rating
        $overallBreakPoints = [
            ceil($maxOverall / 6),
            ceil($maxOverall / 3),
            ceil(2/3 * $maxOverall),
            floor(5/6*$maxOverall),
            $maxOverall + 1
            ];


        foreach ($overallBreakPoints as $i => $pt) {
            if ($overallRank <= $pt) {
                $overallRating = self::SIG_ABOVE_AVG - $i;
                break;
            }
        }


        if ($brandData['num_dry'] > 0) {
            $dryRank = $brandData['dry_rank'];
            $dryOverall = $brandData['dry_brand_count'];
            $dryBreakPoints = [
                ceil($dryOverall / 6),
                ceil($dryOverall / 3),
                ceil(2/3 * $dryOverall),
                floor(5/6*$dryOverall),
                $dryOverall + 1
            ];
            foreach ($dryBreakPoints as $i=>$pt) {
                if ($dryRank <= $pt) {
                    $dryRating = self::SIG_ABOVE_AVG - $i;
                    break;
                }
            }
        }


        if ($brandData['num_wet'] > 0) {
            $wetRank = $brandData['wet_rank'];
            $wetOverall = $brandData['wet_brand_count'];
            $wetBreakPoints = [
                ceil($wetOverall / 6),
                ceil($wetOverall / 3),
                ceil(2/3 * $wetOverall),
                floor(5/6*$wetOverall),
                $wetOverall+1
            ];
            foreach ($wetBreakPoints as $i=>$pt) {
                if ($wetRank <= $pt) {
                    $wetRating = self::SIG_ABOVE_AVG - $i;
                    break;
                }
            }
        }

        $rv = [
            'overallRating' => $overallRating,
            'dryRating' => $dryRating,
            'wetRating' => $wetRating
        ];

        return $rv;


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

                if (!$product->getDiscontinued()) {
                    if ($product->getIsWetFood()) {
                        $wet[] = $product;
                    } else {
                        $dry[] = $product;
                    }
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
