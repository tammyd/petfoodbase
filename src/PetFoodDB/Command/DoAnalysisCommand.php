<?php


namespace PetFoodDB\Command;


use PetFoodDB\Model\PetFood;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class DoAnalysisCommand extends ContainerAwareCommand
{

    protected $input;
    protected $output;

    protected $progress;

    protected function configure()
    {
        $this
            ->setDescription("Do numerical analysis on all products")
            ->setName('products:analyze');
    }

    public function outputProgress($percent) {
        if ($percent > 0) {
            $this->progress->advance();
        }
    }

    /*
     * Note: when you get tired of this taking so long, it's the AnalysisService::getProductDiffStats function that's the bottleneck.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->progress = new ProgressBar($output, 100);
        $this->progress->start();


        $this->input = $input;
        $this->output = $output;

        /* @var \PetFoodDB\Service\AnalysisWrapper $analysisWrapper */
        $analysisWrapper = $this->container->get('analysis.access');

        $data = $analysisWrapper->calcData([$this, 'outputProgress']);
        $analysisWrapper->initDB();
        $analysisWrapper->updateData($data);
        $this->progress->advance();
        $this->progress->finish();

        $this->output->writeln("\n<info>Done Product Analysis! " . count($data). " records analyzed.</info>");
        $this->output->writeln("\n<info>Starting Brand Analysis...</info>");

        $catfoodService = $this->container->get('catfood');
        $brandService = $this->container->get('brand.analysis');
        $brands = $catfoodService->getBrands();
        $this->progress = new ProgressBar($output, count($brands));
        $this->progress->start();
        $brandService->updateDB([$this->progress, 'advance']);

        $this->output->writeln("\n<info>Done! Analyzed " . count($brands) . " brands!</info>");

    }

}
