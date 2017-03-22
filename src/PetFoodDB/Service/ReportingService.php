<?php


namespace PetFoodDB\Service;


class ReportingService
{
    protected $catfoodService;
    protected $dbConnection;

    public function __construct(PetFoodService $catFoodService)
    {
        $this->catFoodService  = $catFoodService;
        $this->dbConnection = $catFoodService->getDb()->getConnection();
    }

    public function getNutritionScoresByType($type) {

        $ratingField = "nutrition_rating";
        $sql = sprintf("select type, type_count, %s as rating, count(*) as count from analysis where type = '%s' group by %s", $ratingField, $type, $ratingField);
        return $this->getQueryResults($sql);

    }

    public function getIngredientScoresByType($type) {
        $ratingField = "ingredients_rating";
        $sql = sprintf("select type, type_count, %s as rating, count(*) as count from analysis where type = '%s' group by %s", $ratingField, $type, $ratingField);
        return $this->getQueryResults($sql);
    }

    public function getScoresByType($type) {
        $sql = sprintf("select type, type_count, (nutrition_rating + ingredients_rating) as score, count(*) as count from analysis where type = '%s' group by score;", $type);
        return $this->getQueryResults($sql);
    }

    protected function getQueryResults($query) {
        $result = $this->dbConnection->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }


}
