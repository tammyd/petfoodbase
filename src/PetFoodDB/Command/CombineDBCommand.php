<?php


namespace PetFoodDB\Command;


use PetFoodDB\Command\Traits\DBTrait;
use PetFoodDB\Model\CatFood;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CombineDBCommand extends ContainerAwareCommand
{

    use DBTrait;

    protected $input;
    protected $output;

    protected function configure()
    {
        $this
            ->setDescription("Combine multiple dbs into one.")
            ->setName('db:combine')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addArgument('brands', InputArgument::REQUIRED, "Comma separated list of brands dbs to combine")
            ->addOption('append', InputOption::VALUE_NONE, null, "If db already exists, use it anyway");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        //create the new master db
        $rv = $this->createDB();
        if ($rv) {
            //an error code
            if ($this->input->getOption('append')) {
                $this->output->writeln("<info>Will be appending to the existing database.</info>");
            } else {
                $this->output->writeln("<info>Error creating the new db. Exiting.</info>");
                return $rv; //error code
            }
        }

        //master db
        $masterFilename = $this->getDBFilepath($this->input->getArgument('name'), $this->container);
        dump("Main db: $masterFilename");
        $mainDB = $this->getDB($masterFilename);

        $brands = explode(",", $this->input->getArgument('brands'));
        $brands = array_map('trim', $brands);

        $sql = [];
        foreach ($brands as $brand) {
            //get the insert sql statements for each db
            if ($this->dbExists($brand, $this->container)) {

                $brandDB = $this->getDBFilepath($brand, $this->container);
                $catfoods = $this->getCatFoodFromDB($brandDB);

                foreach ($catfoods as $catfood) {
                    $mainDB->catfood->insert($catfood->dbModel());
                }

                $this->output->writeln("<comment>Wrote out " . count($catfoods) . " $brand cat foods to $masterFilename.</comment>");
            } else {
                $this->output->writeln("<info>DB for $brand does not exist, skipping</info>");
            }
        }

    }


    protected function getCatFoodFromDB($filename) {
        $db = $this->getDB($filename);
        $brandCatFoods = [];
        $rows = $db->catfood("id > 0");

        //return $this->db->catfood->insert($catfood->dbModel());
        foreach ($rows as $row) {
            $data = iterator_to_array($row);
            $catfood = new CatFood($data);
            $catfood->setId(null);
            $brandCatFoods[] = $catfood;
        }

        return $brandCatFoods;
    }


    protected function createDB() {
        //create the new db
        $command = $this->getApplication()->find('db:create');
        $arguments = array(
            'command' => 'db:create',
            'name'    => $this->input->getArgument('name')
        );
        $createInput = new ArrayInput($arguments);
        return $command->run($createInput, $this->output);
        
    }
}
