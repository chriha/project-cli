<?php

namespace Chriha\ProjectCLI\Console\Input;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\ArrayInput as SymfonyArrayInput;
use Symfony\Component\Console\Input\InputDefinition;

class ArrayInput extends SymfonyArrayInput
{

    private $parameters = [];

    protected $params = [];

    public function __construct( array $parameters, InputDefinition $definition = null )
    {
        parent::__construct( $this->params = $parameters, $definition );
    }

    /**
     * Returns all the provided parameters
     *
     * @param array $prepend
     * @param int $offset
     * @return array
     */
    public function getParameters( array $prepend = [], int $offset = 0 ) : array
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

        return array_merge( $prepend, $params );
    }

    /**
     * {@inheritdoc}
     */
    protected function parse()
    {
        foreach ( $this->parameters as $key => $value )
        {
            if ( '--' === $key ) return;

            if ( 0 === strpos( $key, '--' ) )
            {
                $this->addLongOption( substr( $key, 2 ), $value );
            }
            elseif ( 0 === strpos( $key, '-' ) )
            {
                $this->addShortOption( substr( $key, 1 ), $value );
            }
            else
            {
                $this->addArgument( $key, $value );
            }
        }
    }

    /**
     * Adds a short option value.
     *
     * @param string $shortcut The short option key
     * @param mixed $value The value for the option
     *
     * @throws InvalidOptionException When option given doesn't exist
     */
    private function addShortOption( $shortcut, $value )
    {
        if ( ! $this->definition->hasShortcut( $shortcut ) )
        {
            throw new InvalidOptionException( sprintf( 'The "-%s" option does not exist.', $shortcut ) );
        }

        $this->addLongOption( $this->definition->getOptionForShortcut( $shortcut )->getName(), $value );
    }

    /**
     * Adds a long option value.
     *
     * @param string $name The long option key
     * @param mixed $value The value for the option
     *
     * @throws InvalidOptionException When option given doesn't exist
     * @throws InvalidOptionException When a required value is missing
     */
    private function addLongOption( $name, $value )
    {
        if ( ! $this->definition->hasOption( $name ) )
        {
            throw new InvalidOptionException( sprintf( 'The "--%s" option does not exist.', $name ) );
        }

        $option = $this->definition->getOption( $name );

        if ( null === $value )
        {
            if ( $option->isValueRequired() )
            {
                throw new InvalidOptionException( sprintf( 'The "--%s" option requires a value.', $name ) );
            }

            if ( ! $option->isValueOptional() )
            {
                $value = true;
            }
        }

        $this->options[$name] = $value;
    }

    /**
     * Adds an argument value.
     *
     * @param string $name The argument name
     * @param mixed $value The value for the argument
     *
     * @throws InvalidArgumentException When argument given doesn't exist
     */
    private function addArgument( $name, $value )
    {
        if ( ! $this->definition->hasArgument( $name ) )
        {
            throw new InvalidArgumentException( sprintf( 'The "%s" argument does not exist.', $name ) );
        }

        $this->arguments[$name] = $value;
    }

}
