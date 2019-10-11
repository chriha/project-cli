<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputOption;

class UpCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'up';

    /** @var bool */
    protected $requiresProject = true;


    protected function configure() : void
    {
        $this->setDescription( 'Create and start project containers' )
            ->addOption( 'detach', 'd', InputOption::VALUE_OPTIONAL );
    }

    public function handle( Docker $docker ) : void
    {
        if ( ! empty( $blocked = $docker->hasOccupiedPorts() ) )
        {
            $this->abort( "Ports are already occupied: " . implode( ', ', $blocked ) );
        }

        $process = $docker->process( [ 'up', '-d' ] );

        $this->spinner( 'Starting project', $process );
    }

}
