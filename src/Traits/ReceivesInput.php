<?php

namespace Chriha\ProjectCLI\Traits;

use Symfony\Component\Console\Input\InputInterface;

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
