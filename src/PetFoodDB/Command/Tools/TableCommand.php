<?php


namespace PetFoodDB\Command\Tools;


use PetFoodDB\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class TableCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;

    protected function rightAligned() {
        $rightAligned = new TableStyle();
        $rightAligned->setPadType(STR_PAD_LEFT);
        return $rightAligned;
    }

    protected function centerAligned() {
        $centerAligned = new TableStyle();
        $centerAligned->setPadType(STR_PAD_BOTH);
        return $centerAligned;
    }

    protected function leftAligned() {
        $centerAligned = new TableStyle();
        $centerAligned->setPadType(STR_PAD_RIGHT);
        return $centerAligned;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $style = new OutputFormatterStyle('red');
        $this->output->getFormatter()->setStyle('warning', $style);

    }

    public function outputCSV($data) {
        $outstream = fopen("php://output", 'w');
        array_walk($data,function  (&$vals, $key, $filehandler) {
            fputcsv($filehandler, $vals);
        } , $outstream);
        fclose($outstream);
    }

    public function writeError($msg) {
        $this->output->writeln(sprintf("<error>%s</error>", $msg));
    }

}
