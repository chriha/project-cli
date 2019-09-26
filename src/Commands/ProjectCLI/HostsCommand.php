<?php

namespace Chriha\ProjectCLI\Commands\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Symfony\Component\Console\Input\InputOption;

class HostsCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'hosts';


    public function configure() : void
    {
        $this->setDescription( 'Manage hosts file' );
        $this->addOption( 'search', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '' );
        $this->addOption( 'disable', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '' );
        $this->addOption( 'enable', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '' );
        $this->addOption( 'remove', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '' );
        $this->addOption( 'add', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '' );
    }

    public function handle() : void
    {
        if ( ! $this->option( 'disable' ) && ! $this->option( 'enable' )
            && ! $this->option( 'remove' ) && ! $this->option( 'add' ) )
        {
            $hosts = $this->getHosts();

            foreach ( $hosts as $key => $host )
            {
                $status = '  ✓';

                if ( substr( $host, 0, 1 ) == '#' )
                {
                    $status = '  -';
                    $host   = ltrim( $host, '#' );
                }

                $hosts[$key] = explode( ' ', $host );

                array_unshift( $hosts[$key], $status );
            }

            if ( empty( $hosts ) ) return;

            $this->table( [ 'Status', 'IP', 'Resolution' ], $hosts );
        }
    }

    protected function getHosts() : array
    {
        $hosts = [];

        // TODO: other systems
        $file   = "/private/etc/hosts";
        $isSafe = false;

        $handle = fopen( $file, "r" );

        while ( ( $line = fgets( $handle ) ) !== false )
        {
            $line = trim( $line );

            if ( empty( $line ) ) continue;

            $pos = strpos( $line, 'DO NOT CHANGE THE LINES ABOVE' );

            if ( ! $isSafe && $pos === false ) continue;

            if ( ! $isSafe && ( $isSafe = true ) ) continue;

            $hosts[] = $line;
        }

        fclose( $handle );

        return $hosts;
    }

}
