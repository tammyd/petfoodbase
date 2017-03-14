<?php


namespace PetFoodDB\Command;


use PetFoodDB\Command\Traits\DBTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class InitDBCommand extends ContainerAwareCommand
{

    use DBTrait;

    protected function configure()
    {
        $this
            ->setDescription("Init a new database")
            ->setName('db:create')
            ->addArgument('name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $filename = $this->getDBFilepath($input->getArgument('name'), $this->container);
        if ($this->dbExists($input->getArgument('name'), $this->container)) {
            $filename = $this->getDBFilepath($input->getArgument('name'), $this->container);
            $size = filesize($filename);
            $output->writeln("<error>Cannot create database - $filename already exists with size $size</error>");
            return 1; //error code
        }

        $this->createCatFoodDB($input->getArgument('name'), $this->container);

        $output->writeln("<info>Successfully created database at $filename");

    }
}




