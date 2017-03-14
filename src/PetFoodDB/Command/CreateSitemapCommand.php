<?php

namespace PetFoodDB\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateSitemapCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setDescription("Create a new publically accessible sitemap ")
            ->setName('sitemap:create')
            ->addArgument('input', InputArgument::REQUIRED, "Input filename of links, one per line")
            ->addArgument('output', InputArgument::REQUIRED, "Output filename");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputFile = $input->getArgument('input');
        $outpuFile = $input->getArgument('output');
        $sitemapUtils = $this->container->get('sitemap.utils');
        $sitemapUtils->setLocation($outpuFile);

        $lines = file($inputFile, FILE_IGNORE_NEW_LINES);
        $sitemapUtils->writeSitemap($lines);

    }

} 
