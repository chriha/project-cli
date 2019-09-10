<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument as IA;

class ExecCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'docker:exec';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->setDescription( 'Execute commands in running containers' )
            ->addArgument( 'service', IA::REQUIRED, 'The service you want to execute the command in' )
            ->addArgument( 'commands', IA::IS_ARRAY, 'The command you want to execute' );
    }

    public function handle( Docker $docker ) : void
    {
        passthru( "docker-compose -f {$docker->config()} exec {$this->argument('service')} "
            . implode( ' ', $this->argument( 'commands' ) ) );
    }

}
