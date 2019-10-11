<?php

namespace Chriha\ProjectCLI\Console\Input;

use Symfony\Component\Console\Input\InputInterface as SymfonyInputInterface;

interface InputInterface extends SymfonyInputInterface
{

    /**
     * Returns all the provided parameters
     *
     * @return array
     */
    public function getParameters() : array;

}
