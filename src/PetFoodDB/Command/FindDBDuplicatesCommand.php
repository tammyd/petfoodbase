<?php

namespace PetFoodDB\Command;

use PetFoodDB\Command\Traits\DBTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindDBDuplicatesCommand extends ContainerAwareCommand
{

    use DBTrait;

    protected function configure()
    {
        $this
            ->setDescription("Check DB for duplicated")
            ->setName('db:duplicates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $catfoodService = $this->container->get('catfood');
        $dbConnection = $catfoodService->getDb()->getConnection();

        $sql = "select count(id) as count, id, brand,flavor,moisture from catfood group by brand,flavor,moisture having count > 1  order by count desc";
        $result = $dbConnection->query($sql);

        foreach ($result as $record) {
            $ids = [];
            $sql = sprintf('select * from catfood where brand="%s" and flavor="%s" and moisture=%.02f', $record['brand'], $record['flavor'], $record['moisture']);
            $subResult = $dbConnection->query($sql);

            $output->writeln(sprintf("<comment>%d instances of %s %s with moisture %.02f</comment>", $record['count'],$record['brand'], $record['flavor'], $record['moisture'] ));
            foreach ($subResult as $clone) {
                $output->writeln("\tId: " . $clone['id']);
            }

        }

    }

}
