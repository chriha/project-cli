<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;

class LogsCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'docker:logs';


    public function configure() : void
    {
        $this->setDescription( 'View output from containers' )
            ->addOption( 'follow', 'f', null, 'Follow log output' )
            ->addArgument( 'services', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Services to output' );
    }

    public function handle( Docker $docker ) : void
    {
        $follow = $this->option( 'follow' ) ? '-f' : '';

        passthru( "docker-compose -f " . $docker->config() . " logs {$follow} "
            . implode( ' ', $this->argument( 'services' ) ) );
    }

}
