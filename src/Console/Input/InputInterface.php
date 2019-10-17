<?php

namespace Chriha\ProjectCLI\Console\Input;

use Symfony\Component\Console\Input\InputInterface as SymfonyInputInterface;

interface InputInterface extends SymfonyInputInterface
{

    /**
     * Returns all the provided parameters
     *
     * @param array $prepend
     * @param int $offset
     * @return array
     */
    public function getParameters( array $prepend = [], int $offset = 0 ) : array;

}
