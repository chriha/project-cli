<?php

namespace Chriha\ProjectCLI\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ReceivesInput
{

    /** @var InputInterface */
    public $input;

    /**
     * Get an argument from the input list.
     *
     * @param string $key
     * @return mixed
     */
    protected function argument( $key )
    {
        return $this->input->getArgument( $key );
    }

    /**
     * Get an option from the input list.
     *
     * @param string $key
     * @return mixed
     */
    protected function option( $key )
    {
        return $this->input->getOption( $key );
    }

}
