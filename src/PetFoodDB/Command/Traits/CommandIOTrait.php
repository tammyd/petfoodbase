<?php

namespace PetFoodDB\Command\Traits;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait CommandIOTrait
{
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return $this
     */
    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @return \Symfony\Component\Console\Input\InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return $this
     */
    public function setOutput($output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

}
