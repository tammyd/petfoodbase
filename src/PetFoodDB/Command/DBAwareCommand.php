<?php


namespace PetFoodDB\Command;


use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class DBAwareCommand extends ContainerAwareCommand
{

    protected function execute(InputInterface $input, OutputInterface $output) {
        
    }
}
