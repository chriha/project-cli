<?php

namespace Chriha\ProjectCLI\Console\Input;

use Symfony\Component\Console\Input\InputDefinition;

class ArgvInput extends \Symfony\Component\Console\Input\ArgvInput
{

    /** @var array */
    protected $parameters = [];


    /**
     * @param array|null $argv An array of parameters from the CLI (in the argv format)
     * @param InputDefinition|null $definition A InputDefinition instance
     */
    public function __construct( array $argv = null, InputDefinition $definition = null )
    {
        $argv = is_null( $argv ) ? $_SERVER['argv'] : $argv;

        $this->parameters = array_slice( $argv, 2 );

        parent::__construct( $argv, $definition );
    }

    /**
     * Returns all the provided parameters
     *
     * @return array
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

}
