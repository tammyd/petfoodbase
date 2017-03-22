<?php


namespace PetFoodDB\Service;


use PetFoodDB\Model\PetFood;
use PetFoodDB\Traits\LoggerTrait;
use Symfony\Component\Stopwatch\Stopwatch;

class AnalysisWrapper extends BaseService
{

    const ANALYSIS_DB_NAME = 'analysis';

    use LoggerTrait;

    protected $catfoodService;
    protected $analysisService;

    public function __construct(\NotORM $db, PetFoodService $catfoodService, NewAnalysisService $analysisService)
    {
        parent::__construct($db);
        $this->catfoodService = $catfoodService;
        $this->analysisService = $analysisService;
    }

    public function getProductAnalysis(PetFood $catFood) {
        $row = $this->db->analysis[$catFood->getId()];

        return [
            'type' => $row['type'],
            'count' => $row['type_count'],
            'protein' => $row['protein_sd'],
            'carbohydrates' => $row['carbohydrates_sd'],
            'fat' => $row['fat_sd'],
            'fibre' => $row['fibre_sd'],
            'moisture' => $row['moisture_sd'],
            'calories' => $row['calories_sd'],
            'ingredients_rating' => $row['ingredients_rating'],
            'nutrition_rating' => $row['nutrition_rating']
        ];
    }



    
    public function updateDB() {
        
        $this->initDB();

        $data = $this->calcData();
        return $this->updateData($data);
        
    }

    public function initDB() {
        //1: drop existing db
        $this->dropTable();

        //2: recreate db
        $this->createTable();
    }

    
    public function calcData(callable $progressFtn = null) {

        return $this->analysisService->getData($progressFtn);
        
    }

    public function updateData(array $data) {
        foreach ($data as $id => $row) {
            $model = [
                'id' => $id,
                'type' => $row['type'], //todo check all the data
                'protein_sd' => $row['protein'],
                'fat_sd' => $row['fat'],
                'fibre_sd' => $row['fibre'],
                'moisture_sd' => $row['moisture'],
                'calories_sd' => $row['calories'],
                'carbohydrates_sd' => $row['carbohydrates'],
                'ash_sd' => $row['other'],
                'nutrition_rating' => $row['nutrition_rating'],
                'ingredients_rating' => $row['ingredients_rating'],
                'type_count' => $row['count']
                
            ];

            $this->db->analysis->insert($model);

        }


    }

    protected function dropTable() {
        $table = self::ANALYSIS_DB_NAME;
        $pdo = $this->getPDO();

        $pdo->query("Drop table if exists $table");
    }

    protected function createTable() {
        $table = self::ANALYSIS_DB_NAME;
        $pdo = $this->getPDO();

        $sql = <<<EOT
        CREATE TABLE "$table" (
        "id" INTEGER PRIMARY KEY  NOT NULL ,
"type" VARCHAR NOT NULL ,
"type_count" NUMERIC NOT NULL  DEFAULT (0) ,
"protein_sd" NUMERIC NOT NULL  DEFAULT (0) ,
"fat_sd" NUMERIC NOT NULL  DEFAULT (0) ,
"fibre_sd" NUMERIC NOT NULL  DEFAULT (0) ,
"moisture_sd" NUMERIC NOT NULL  DEFAULT (0) ,
"ash_sd" NUMERIC NOT NULL  DEFAULT (0) ,
"carbohydrates_sd" NUMERIC NOT NULL DEFAULT (0) ,
"calories_sd" NUMERIC NOT NULL DEFAULT (0) ,
"nutrition_rating" NUMERIC NOT NULL  DEFAULT (0) , 
"ingredients_rating" NUMERIC NOT NULL  DEFAULT (0))
EOT;

        $pdo->query($sql);

    }

}
