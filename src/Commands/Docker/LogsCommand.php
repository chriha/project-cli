<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;

class LogsCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'docker:logs';

    /** @var string */
    protected $description = 'View output from containers';


    public function configure() : void
    {
        $this->addOption( 'follow', 'f', null, 'Follow log output' )
            ->addArgument( 'services', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Services to output' );
    }

    public function handle() : void
    {
        $params = [ 'logs' ];

        if ( $this->option( 'follow' ) )
        {
            $params[] = '-f';
        }

        $params = array_merge( $params, $this->argument( 'services' ) );

        $this->call( 'docker:compose', $params );
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE;
    }

}
