<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class StatusCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'status';

    /** @var string */
    protected $description = 'List all service containers and show their status';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->addOption( 'activity', 'a', InputOption::VALUE_NONE, 'Show activity stats for each container' )
            ->addArgument( 'service', InputArgument::OPTIONAL, 'Restrict status info to specified service' );
    }

    public function handle( Docker $docker )
    {
        if ( $service = $this->argument( 'service' ) )
        {
            $this->showTable( $docker->ps( $service ) );

            return;
        }

        $this->task( 'Identifying services', function() use ( $docker, &$services )
        {
            $services = array_flip( $docker->services() );
        } );

        if ( empty( $services ) )
        {
            $this->abort( 'No services found!' );
        }

        $this->task( 'Checking service status', function() use ( $docker, &$services )
        {
            foreach ( $services as $service => $value )
            {
                $services[$service] = $docker->ps( $service );
            }
        } );

        if ( $this->option( 'activity' ) )
        {
            $this->task( 'Checking activity', function() use ( $docker, &$services )
            {
                foreach ( $services as $service => $containers )
                {
                    foreach ( $containers as $key => $container )
                    {
                        $services[$service][$key] = array_merge(
                            $container, $docker->stats( $container['name'] ) );
                    }
                }
            } );
        }

        $cols = [];

        foreach ( $services as $service => $containers )
        {
            foreach ( $containers as $container )
            {
                array_unshift( $container, $service );

                $cols[] = $container;
            }
        }

        $this->showTable( $cols );
    }

    protected function showTable( array $data ) : void
    {
        if ( empty( $data ) )
        {
            $this->abort( 'No data available' );
        }

        $headers = [ 'Service', 'Container', 'Status', 'Ports (host:container)' ];

        if ( count( $data[0] ) > 4 )
        {
            $headers = array_merge( $headers, [
                'CPU %', 'Memory %', 'Memory Usage / Limit'
            ] );
        }

        $this->table( $headers, $data );
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE;
    }

}
