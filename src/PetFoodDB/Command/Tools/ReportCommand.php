<?php


namespace PetFoodDB\Command\Tools;

use PetFoodDB\Service\NewAnalysisService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCommand extends TableCommand
{
    protected $input;
    protected $output;
    
    protected function configure()
    {
        $this
            ->setDescription("Display product scores")
            ->setName('db:report')
            ->addOption("score", 's', null, "Show Score Summary")
            ->addOption("dry", 'd', null, "Show Dry Data")
            ->addOption("wet", 'w', null, "Show Wet Data")
            ->addOption('csv');

    }

    protected function getRatingTable($type, $ratingType) {

        $reportService = $this->container->get('reporting');
        if ($ratingType=='ingredients') {
            $result = $reportService->getIngredientScoresByType($type);
        } elseif ($ratingType=='nutrition') {
            $result = $reportService->getNutritionScoresByType($type);
        }
        
        $table = new Table($this->output);
        $title = sprintf("%s Cat Food - %s", ucwords($type), ucwords($ratingType));
        $table->setHeaders(
            [
                [new TableCell($title, ['colspan' => 3])],
                ['Rating', 'Count', 'Percentage']
            ])
            ->setColumnStyle(0, $this->rightAligned())
            ->setColumnStyle(1, $this->rightAligned())
            ->setColumnStyle(2, $this->rightAligned());
        foreach ($result as $row) {
            $percentage = sprintf("%.02f", (100*$row['count']/$row['type_count']));
            $rating = sprintf("%.01f", $row['rating']);
            $table->addRow([$rating, $row['count'], $percentage]);
        }

        return $table;

    }

    protected function getScoreData($dbConnection, $type) {
        $sql = "select count(*) as count, type,  type_count, score from (select *, ingredients_rating+nutrition_rating as score from analysis) where type='$type' group by score order by score";
        $result = $dbConnection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    protected function getScoreTable($type) {


        $reportService = $this->container->get('reporting');
        $result = $reportService->getScoresByType($type);

        $table = new Table($this->output);
        $title = sprintf("%s Cat Food Scores", ucwords($type));
        $table->setHeaders(
            [
                [new TableCell($title, ['colspan' => 3])],
                ['Score', 'Count', 'Percentage']
            ])
            ->setColumnStyle(0, $this->rightAligned())
            ->setColumnStyle(1, $this->rightAligned())
            ->setColumnStyle(2, $this->rightAligned());

        foreach ($result as $row) {
            $percentage = sprintf("%.02f", (100*$row['count']/$row['type_count']));
            $score = sprintf("%.01f", $row['score']);
            $table->addRow([$score, $row['count'], $percentage]);
        }

        return $table;
    }

    protected function getCombinedScoreTable($dbConnection) {
        $table = new Table($this->output);
        $title = "%s Cat Food Scores";
        $table->setHeaders(
            [
                [
                    new TableCell(sprintf($title, 'Wet'), ['colspan' => 3]),
                    new TableCell(sprintf($title, 'Dry'), ['colspan' => 3]),
                    new TableCell(sprintf($title, 'Combined'), ['colspan' => 3]),
                ],
                ['Score', 'Count', '%', 'Score', 'Count', '%', 'Score', 'Count', '%']
            ])
            ->setColumnStyle(0, $this->centerAligned())
            ->setColumnStyle(1, $this->rightAligned())
            ->setColumnStyle(2, $this->rightAligned())
            ->setColumnStyle(3, $this->centerAligned())
            ->setColumnStyle(4, $this->rightAligned())
            ->setColumnStyle(5, $this->rightAligned())
            ->setColumnStyle(6, $this->centerAligned())
            ->setColumnStyle(7, $this->rightAligned())
            ->setColumnStyle(8, $this->rightAligned());

        $dryResults = $this->getScoreData($dbConnection, 'dry');
        $wetResults = $this->getScoreData($dbConnection, 'wet');

        
        for($score=2, $w = 0, $d=0; $score<=10; $score++) {

            $count = 0;
            $dryRow = [
                'score' => -1
            ];
            $wetRow = [
                'score' => -1
            ];
            if (isset($dryResults[$d])) {
                $dryRow = $dryResults[$d];
            }
            if (isset($wetResults[$w])) {
                $wetRow = $wetResults[$w];
            }

            $hasDry = false;
            $hasWet = false;
            if ($dryRow['score'] == $score) {
                $hasDry = true;
                $d++;
            }
            if ($wetRow['score'] == $score) {
                $hasWet = true;
                $w++;
            }
            $row = [$score];
            if ($hasWet) {
                $percentage = sprintf("%.02f", (100*$wetRow['count']/$wetRow['type_count']));
                $row[] = $wetRow['count'];
                $row[] = $percentage;
                $count += $wetRow['count'];
            } else {
                $row[] = '-';
                $row[] = '-';
            }
            $row[] = $score;
            if ($hasDry) {
                $percentage = sprintf("%.02f", (100*$dryRow['count']/$dryRow['type_count']));
                $row[] = $dryRow['count'];
                $row[] = $percentage;
                $count += $dryRow['count'];
            } else {
                $row[] = '-';
                $row[] = '-';
            }

            $row[] = $score;
            $row[] = $count;


            $scoreCount = (isset($wetRow['count']) ? $wetRow['count'] : 0) + (isset($dryRow['count']) ? $dryRow['count'] : 0);
            $typeCount = (isset($wetRow['type_count']) ? $wetRow['type_count'] : 0) + (isset($dryRow['type_count']) ? $dryRow['type_count'] : 0);
            $percentage = (100 * ($scoreCount)) / ($typeCount);
            $percentage = sprintf("%.02f", $percentage);

            $row[] = $percentage;

            $table->addRow($row);

        }

        return $table;
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->input = $input;
        $this->output = $output;

        $catfoodService = $this->container->get('catfood');
        $dbConnection = $catfoodService->getDb()->getConnection();

        if ($this->input->getOption('wet')) {
            $wetIngredients = $this->getRatingTable("wet", "ingredients");
            $wetNutrition = $this->getRatingTable("wet", "nutrition");
            $wetScoreTable = $this->getScoreTable('wet');

            $wetIngredients->render();
            $wetNutrition->render();
            $wetScoreTable->render();
        }

        if ($this->input->getOption('dry')) {

            $dryIngredients = $this->getRatingTable("dry", "ingredients");
            $dryNutrition = $this->getRatingTable("dry", "nutrition");
            $dryScoreTable = $this->getScoreTable('dry');

            $dryIngredients->render();
            $dryNutrition->render();
            $dryScoreTable->render();
        }


        if ($this->input->getOption('score')) {

            $combinedScoreTable = $this->getCombinedScoreTable($dbConnection);
            $combinedScoreTable->render();
        }

        $this->output->writeln(sprintf("<info>Above SD: %0.03f. Sig Above SD: %0.03f. ", NewAnalysisService::STAT_ABOVE_AVERAGE_SD, NewAnalysisService::STAT_SIG_ABOVE_SD));



    }



}
