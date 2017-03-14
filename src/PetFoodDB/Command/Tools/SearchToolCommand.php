<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Service\AnalyzeIngredients;
use PetFoodDB\Service\CatFoodService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SearchToolCommand extends TableCommand
{

    protected function configure()
    {
        $this
            ->setDescription("Perform Specific Searches")
            ->setName('db:task')
            ->addArgument('task', InputArgument::REQUIRED, "The specific search. One of [hypo|...]");


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $command = $input->getArgument('task');
        $products = null;
        switch ($command) {
            case 'hypo':
                $products = $this->getHypoallegenicFoods($this->container->get('catfood'), $this->container->get('ingredient.analysis'));
                break;
        }

        $this->displayCatFoodTable($products);


    }


    public static function getHypoallegenicFoods(CatFoodService $catfoodService, AnalyzeIngredients $ingredientAnalysisService) {
        $MIN_SCORE = 6;

        $allergens = AnalyzeIngredients::getCommonAllergens();
        $seafoods = AnalyzeIngredients::getSeafoodProteins();

        $queryStrings = [];

        $queryStrings[] = "-kitten";
        $queryStrings[] = "-beef -lamb -corn -cornmeal -gluten -meat -chicken -turkey";
        $queryStrings[] = "-soy -cheese -milk -egg -soybean"; //todo use $allergens


        $chunks = array_chunk($seafoods, 5);
        foreach ($chunks as $arr) {
            $query = "";
            foreach ($arr as $seafood) {
                $words = explode(' ' , $seafood);
                foreach ($words as $word) {
                    $query = sprintf("%s -%s", $query, $word);
                }
            }
            $queryStrings[] = $query;
        }

        $ids = [];
        foreach ($queryStrings as $query) {
            $result = $catfoodService->textSearch($query);

            $ids[] = array_map(function($cf) {
                return $cf->getId();
            }, $result);

        }

        //also get the cat foods with artifical colors, and remove them from the list
        //the NOT query with a string is kinda flaky, so we'll do it this way
        $dbConnection = $catfoodService->getDb()->getConnection();
        $artificalColors = AnalyzeIngredients::getArtificialColors();
        $artificalIds = [];
        foreach ($artificalColors as $ac) {
            $sql = "SELECT id FROM catfood_search WHERE (catfood_search match 'catfood \"$ac\"') GROUP BY brand, flavor, ingredients, catfood;";
            $result = $dbConnection->query($sql)->fetchAll();

            $artIds = array_map(function ($x) { return $x['id'];}, $result);
            $artificalIds = array_merge($artIds, $artificalIds);
        }


        $artificalIds = array_unique($artificalIds);

        $result = call_user_func_array('array_intersect',$ids);
        $result = array_diff($result, $artificalIds);

        $products = [];
        foreach ($result as $id) {
            $product = $catfoodService->getById($id);
            $analysis = $catfoodService->getDb()->analysis[$id];
            $nut = $analysis['nutrition_rating'];
            $ing = $analysis['ingredients_rating']; //$ingredientAnalysisService->calcIngredientScore($product, false); ;
            $score = $nut + $ing;

            if ($score >= $MIN_SCORE) {

                $product->addExtraData('nutrition_rating', $nut);
                $product->addExtraData('ingredients_rating', $ing);
                $product->addExtraData('score', $score);

                $products[] = $product;
            }
            
        }

        usort($products, function ($productA, $productB) {

            $ingScoreA = $productA->getExtraData('ingredients_rating');
            $ingScoreB = $productB->getExtraData('ingredients_rating');
            if ($ingScoreA !== $ingScoreB) {


                return ($ingScoreA < $ingScoreB) ? -1 : 1;

            }

            $nutScoreA = $productA->getExtraData('nutrition_rating');
            $nutScoreB = $productB->getExtraData('nutrition_rating');

            if ($nutScoreA == $nutScoreB) {
                return 0;
            }

            return ($nutScoreA < $nutScoreB) ? -1 : 1;
        });
        
        
        return $products;


    }


    protected function displayCatFoodTable(array $products) {
        $baseUrl = $this->container->config['app.base_url'];
        $table = new Table($this->output);
        $table->setHeaders(
            [
                ['Id', 'Type', 'Brand', 'Flavor', 'Score', 'Nut Score', "Ing Score", 'Url']
            ])
            ->setColumnStyle(0, $this->leftAligned())
            ->setColumnStyle(1, $this->leftAligned())
            ->setColumnStyle(2, $this->leftAligned())
            ->setColumnStyle(3, $this->leftAligned())
            ->setColumnStyle(4, $this->rightAligned())
            ->setColumnStyle(5, $this->rightAligned())
            ->setColumnStyle(6, $this->rightAligned())
            ->setColumnStyle(7, $this->leftAligned());


        foreach ($products as $product) {
            $flavor = mb_strimwidth($product->getFlavor(),0, 45, '..');
            $url = sprintf("%s/product/%d", $baseUrl,$product->getId());
            $type = $product->getIsWetFood() ? "Wet" : "Dry";
            $table->addRow(
                [
                    $product->getId(),
                    $type,
                    $product->getBrand(),
                    $flavor,
                    $product->getExtraData('score'),
                    $product->getExtraData('nutrition_rating'),
                    $product->getExtraData('ingredients_rating'),
                    $url
                ]);

        }

        $table->render();
        $this->output->writeln(sprintf("<info>Table contained %d products.</info>", count($products)));

    }

}
