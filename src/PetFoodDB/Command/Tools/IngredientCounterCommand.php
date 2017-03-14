<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Service\AnalyzeIngredients;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IngredientCounterCommand extends TableCommand
{
    protected function configure()
    {
        $this
            ->setDescription("Count how many ingredients are in the cat foods")
            ->setName('db:ingredients')
            ->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'Count ingredients for a specific product');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if ($this->input->getOption('id')) {
            $this->byProduct($this->input->getOption('id'));
            return;
        }

        $allProducts = $this->container->catfood->getAll();
        $counts = [];
        foreach ($allProducts as $product) {
            $ingredients = AnalyzeIngredients::parseIngredients($product);
            $count = count($ingredients);
            if (isset($counts[$count])) {
                $counts[$count]['count'] = $counts[$count]['count']+1;
                $counts[$count]['ids'][] = $product->getId();
            } else {
                $counts[$count] = [
                    'count' => 1,
                    'ids' => [$product->getId()]
                ];
            }

        }

        ksort($counts);
        $table = $this->buildTable(count($allProducts), $counts);
        $table->render();




    }

    protected function byProduct($id) {
        $product = $this->container->catfood->getById($id);
        $ingredients = AnalyzeIngredients::parseIngredients($product);
        $this->output->writeln(sprintf("<info>[%d]</info> <comment>%s</comment> has <info>%d</info> ingredients", $id, $product->getDisplayName(), count($ingredients)));

    }

    protected function buildTable($total, $counts) {
        $counter = 0;
        $table = new Table($this->output);
        $maxIds = 8;

        foreach ($counts as $num=>$countData) {


            $ids = implode(",", array_slice($countData['ids'], 0, $maxIds));
            if (count($countData['ids']) > $maxIds) {
                $ids = "[$ids,...]";
            } else {
                $ids = "[$ids]";
            }

            $row = [
                $num,
                $countData['count'],
                sprintf("%0.02f", 100*$countData['count']/$total),
                "$ids"
            ];
            $counter+=$countData['count'];
            $table->addRow($row);
        }

        $table->setHeaders([
            [new TableCell("<comment>Ingredients for <info>$counter</info> products.</comment>", ['colspan' => 4])],
            ['# Ingredients', '# Products', '% of Total', 'Ids']
            ])
            ->setColumnStyle(0, $this->centerAligned())
            ->setColumnStyle(1, $this->centerAligned())
            ->setColumnStyle(2, $this->rightAligned());
        return $table;
    }


}
