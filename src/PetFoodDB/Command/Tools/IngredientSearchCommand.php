<?php


namespace PetFoodDB\Command\Tools;


use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IngredientSearchCommand extends ListTopIngredientsCommand
{

    protected function configure()
    {
        $this
            ->setDescription("List the products that contain an ingredient")
            ->setName('ingredients:search')
            ->addArgument('search', InputArgument::REQUIRED, "Ingredient To Search For");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->input = $input;
        $this->output = $output;
        $baseUrl = $this->container->config['app.base_url'];

        $catfoodService = $this->container->get('catfood');
        $all = $catfoodService->getAll();
        $found = [];
        $ingredient = strtolower($input->getArgument('search'));
        foreach ($all as $product) {
            $ingredients = $this->getIngredients($product);
            if (in_array($ingredient, $ingredients)) {
                $found[] = $product;
            }
        }

        if (empty($found)) {
            $output->writeln("<comment>$ingredient</comment> not found.");
            return;
        }

        $table = new Table($output);
        $table->setHeaders(
            [
                ['Id', 'Edit', 'Brand', 'Flavor']
            ]);
        foreach ($found as $product) {
            $id = $product->getId();
            $brand = $product->getBrand();
            $flavor = mb_strimwidth($product->getFlavor(),0, 15, '..');
            $table->addRow([$id, sprintf("%s/admin/update/%d", $baseUrl, $product->getId()), $brand, $flavor]);
        }

        $table->render();


    }

}
