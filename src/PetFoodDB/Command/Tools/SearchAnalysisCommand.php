<?php


namespace PetFoodDB\Command\Tools;

use PetFoodDB\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchAnalysisCommand extends TableCommand
{
    protected $input;
    protected $output;

    protected function configure()
    {
        $this
            ->setDescription("Search the db by score -- total, ingredient or nutrition")
            ->setName('db:search')
            ->addOption("score", null, InputOption::VALUE_OPTIONAL , "Search for Records Matching Score")
            ->addOption("nutrition", null, InputOption::VALUE_OPTIONAL , "Search for Records Matching Nutrition Score")
            ->addOption("ingredients", null, InputOption::VALUE_OPTIONAL , "Search for Records Matching Ingredients Score")
            ->addOption("type",null, InputOption::VALUE_REQUIRED , "Search for Records Matching Type [wet|dry]");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $baseUrl = $this->container->config['app.base_url'];
        $sql = $this->buildSearchSQL($input->getOption('type'), $input->getOption('nutrition'), $input->getOption('ingredients'), $input->getOption('score'));
        if (!$sql) {
            return;
        }


        $catfoodService = $this->container->get('catfood');
        $dbConnection = $catfoodService->getDb()->getConnection();

        $result = $dbConnection->query($sql)->fetchAll();

        $table = new Table($this->output);
        $table->setHeaders(
            [
                ['Id', 'Nut', "Ing ", 'Brand', 'Flavor', 'Url', 'Amazon?']
            ])
            ->setColumnStyle(0, $this->centerAligned())
            ->setColumnStyle(1, $this->rightAligned())
            ->setColumnStyle(2, $this->rightAligned())
            ->setColumnStyle(2, $this->leftAligned())
        ;
        foreach ($result as $row) {
            $flavor = mb_strimwidth($row['flavor'],0, 15, '..');
            $url = sprintf("%s/product/%d", $baseUrl, $row['id']);
            $asin = $row['asin'] ? "Yes" : "No";
            $table->addRow([$row['id'], $row['nutrition_rating'], $row['ingredients_rating'], $row['brand'], $flavor, $url, $asin]);

        }
        $table->addRow(['----','---','---','----------','---------------', '---']);
        $table->addRow([
            new TableCell('<fg=magenta>Total</>',  ['colspan' => 5]), count($result)])
            ->setColumnStyle(0, $this->leftAligned());

        $table->render();

    }

    protected function buildSearchSQL($type, $nutScore = null, $ingScore = null, $score = null) {
        $allowedTypes = ["wet", "dry"];
        if (!in_array($type, $allowedTypes)) {
            $this->output->writeln("<error>Type should be one of [dry|wet]</error>");
            return;
        }

        if (!$nutScore && !$ingScore && !$score) {
            $this->output->writeln("<error>Must specify at least one of ingredient, nutrition or score</error>");
            return;
        }

        $andSql = "";
        if ($score) {
            $score = floatval($score);
            $andSql = " AND (nutrition_rating + ingredients_rating) = $score";
        }
        if ($nutScore) {
            $nutScore = floatval($score);
            $andSql = " AND nutrition_rating = $nutScore";
        }
        if ($ingScore) {
            $ingScore = floatval($score);
            $andSql = " AND ingredients_rating = $ingScore";
        }

        $sql = "SELECT * from analysis, catfood where  analysis.id=catfood.id  and type = '$type' $andSql order by id";
        return $sql;

    }


}
