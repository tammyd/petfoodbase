<?php

namespace PetFoodDB\Command;

use Slim\Slim;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ContainerAwareCommand extends Command
{
    protected $container;

    public function __construct(\Slim\Helper\Set $container, $name = null)
    {
        parent::__construct($name); 
        $this->container = $container;
        
    }
    

}
