<?php

namespace Chriha\ProjectCLI\Console\Input;

use Symfony\Component\Console\Input\ArrayInput as SymfonyArrayInput;
use Symfony\Component\Console\Input\InputDefinition;

class ArrayInput extends SymfonyArrayInput
{

    protected $params = [];

    public function __construct( array $parameters, InputDefinition $definition = null )
    {
        parent::__construct( $this->params = $parameters, $definition );
    }

    /**
     * Returns all the provided parameters
     *
     * @return array
     */
    public function getParameters() : array
    {
        $params = [];

        foreach ( $this->params as $key => $value )
        {
            if ( $key === 'command' ) continue;

            if ( is_string( $key ) )
            {
                $params[] = $key;
            }

            if ( is_string( $value ) )
            {
                $params[] = $value;
            }
        }

        return $params;
    }

}
