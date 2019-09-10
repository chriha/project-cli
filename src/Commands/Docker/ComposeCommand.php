<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;

class ComposeCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'docker:compose';

    /** @var bool */
    protected $requiresProject = true;

    /** @var bool */
    protected $hasDynamicOptions = true;


    public function configure() : void
    {
        $this->setDescription( 'Run docker-compose commands' )
            ->addArgument( 'commands', InputArgument::IS_ARRAY | InputArgument::OPTIONAL );
    }

    public function handle( Docker $docker ) : void
    {
        passthru( "docker-compose -f {$docker->config()} " . implode( ' ', $this->additionalArgs() ) );
    }

}
