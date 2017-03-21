<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Command\ContainerAwareCommand;
use PetFoodDB\Command\Traits\DBTrait;
use PetFoodDB\Model\PetFood;
use PetFoodDB\Traits\ArrayTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class ListTopIngredientsCommand extends ContainerAwareCommand
{

    use DBTrait;
    use ArrayTrait;
    protected $input;
    protected $output;

    protected function configure()
    {
        $this
            ->setDescription("list the top ingredients")
            ->setName('ingredients:top')
            ->addArgument('position', InputArgument::OPTIONAL, "List nth argument - use 0 for all");
    }

    protected function mergeCounts($arr1, $arr2) {
        $allKeys = array_unique(array_merge(array_keys($arr1), array_keys($arr2)));
        $allKeys = array_filter($allKeys);

        $counts = [];
        foreach ($allKeys as $key) {
            $key = trim($key);
            if (!$key) {
                continue;
            }
            $counts[$key]  = $this->getArrayValue($arr1, $key, 0) + $this->getArrayValue($arr2, $key, 0);
        }

        return $counts;


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->input = $input;
        $this->output = $output;
        $catfoodService = $this->container->get('catfood');
        $all = $catfoodService->getAll();
        $position = (int)$input->getArgument('position');

        if ($position == 0) {
            //get all ingredients;
            $position = 1;
            $topIngredientCounts = [];
            for ($position = 1; $position <= 100; $position++) {
                $ingredientCounts = $this->getNthIngredientCounts($all, $position);
                if (count($ingredientCounts)) {
                    $topIngredientCounts = $this->mergeCounts($ingredientCounts, $topIngredientCounts);
                }
            }

        } else {

            if ($position < 1) {
                $position = 1;
            }
            $topIngredientCounts = $this->getNthIngredientCounts($all, $position);
        }

        arsort($topIngredientCounts);

        $output->writeln("<info>Top ingredients and the number of products:</info>");
        foreach ($topIngredientCounts as $ing=>$count) {
            $output->writeln("* <comment>$ing ($count)</comment>");
        }

    }
    

    protected function getNthIngredientCounts($allCatFood, $n) {
        $ingredients = [];
        $ingredientCounts = [];
        foreach ($allCatFood as $catfood) {
            $ingredients[$catfood->getId()] = $this->getNthIngredient($catfood, $n);
        }

//        foreach ($ingredients as $id=>$ingredient) {
//            $this->output->writeln("$id: $ingredient");
//        }


        $uniqueIngredients = array_unique($ingredients);
        sort($uniqueIngredients);
        foreach ($uniqueIngredients as $ingredient) {
            $ingredientCounts[$ingredient] = 0;
            foreach ($ingredients as $productIngredient) {
                if ($productIngredient == $ingredient) {
                    $ingredientCounts[$ingredient]++;
                }
            }
        }

        return $ingredientCounts;

    }

    protected function getTopIngredient($catfood) {
        return $this->getNthIngredient($catfood, 1);
    }

    protected function getIngredients(PetFood $catFood) {
        $ing = $catFood->getIngredients();
        $ingredients = array_map('trim', explode(",", $ing));

        return array_map('strtolower', $ingredients);
    }

    protected function getNthIngredient($catfood, $n) {

        $x = (int) $n -1;
        $ing = $catfood->getIngredients();
        $ingredients = array_map('trim', explode(",", $ing));

        if (isset($ingredients[$x])) {
            return strtolower($ingredients[$x]);
        } else {
            return null;
        }
    }
}
