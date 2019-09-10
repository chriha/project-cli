<?php

namespace Chriha\ProjectCLI\Commands\Ssh;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Libraries\Ssh\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ConfigCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'ssh:config';


    public function configure() : void
    {
        $this->setDescription( 'Manage the SSH config' );
        $this->addArgument( 'host', InputArgument::OPTIONAL, 'Specify the host' );
        $this->addOption( 'set', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Set a key for the specified host' );
        $this->addOption( 'remove', null, InputOption::VALUE_NONE, 'Remove the specified host' );
    }

    public function handle() : void
    {
        $config = new Config( $_SERVER['HOME'] . "/.ssh/config" );

        if ( $this->argument( 'host' ) )
        {
            $this->handleHost( $config );
        }
        else
        {
            $this->table( [ 'Host' ], array_map( function( $key )
            {
                return [ $key ];
            }, array_keys( $config->get() ) ), 'compact' );
        }
    }

    protected function handleHost( Config $config ) : void
    {
        if ( $this->option( 'remove' ) )
        {
            $config->remove( $this->argument( 'host' ) );
            $this->info( "Host '{$this->argument( 'host' )}' removed" );
            exit;
        }

        if ( $this->option( 'set' ) )
        {
            foreach ( $this->option( 'set' ) as $item )
            {
                [ $key, $value ] = explode( '=', $item );

                $config->set( $this->argument( 'host' ), $key, $value );
            }

            $config->save();
        }

        $host = $config->get( $this->argument( 'host' ) );

        if ( ! $host )
        {
            $this->exit( 'No host found!' );
        }

        $rows = [];

        foreach ( $host as $key => $value )
        {
            if ( is_null( $value ) )
            {
                $value = "\e[33m(not set)\e[39m";
            }

            $rows[$key] = [ $key, $value ];
        }

        $this->table( [ 'Key', 'Value' ], $rows, 'compact' );
    }

}
