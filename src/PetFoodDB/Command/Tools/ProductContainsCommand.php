<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Model\PetFood;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProductContainsCommand extends TableCommand
{

    protected function configure()
    {
        $this
            ->setDescription("Check if a product contains a certain ingredient")
            ->setName('products:ingredients')
            ->addOption("id", 'i', InputOption::VALUE_REQUIRED, "product id")
            ->addOption("ingredients", 'g', InputOption::VALUE_REQUIRED, "other ingredients, comma seperated");


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        parent::execute($input, $output);
        /* @var \PetFoodDB\Service\CatFoodService $catfoodService */
        $catfoodService = $this->container->get('catfood');
        $id = $input->getOption('id');
        $product = $catfoodService->getById($id);
        if (!$product) {
            $this->writeError("Product $id doesn't exist");
            return;
        }

        $ingredients = $this->getIngredients($product);
        
        $toLookFor = ['guar gum', 'carrageenan'];
        if ($input->getOption('ingredients')) {
            $toLookFor = array_merge($toLookFor, array_map('trim', explode(",", $input->getOption('ingredients'))));
        }

        $toLookFor = array_unique($toLookFor);
        foreach ($toLookFor as $ing) {
            if (in_array(strtolower($ing), $ingredients)) {
                $this->output->writeln(sprintf("Product #%d (%s) <warning>DOES</warning> contain <comment>$ing</comment>", $product->getId(), $product->getDisplayName()));
            } else {
                $this->output->writeln(sprintf("Product #%d (%s) <info>DOES NOT</info> contain  <comment>$ing</comment>", $product->getId(), $product->getDisplayName()));
            }
        }
    }
    

}
