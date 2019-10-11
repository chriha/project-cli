<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class ComposeCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'docker:compose';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->setDescription( 'Run docker-compose commands' )
            ->addDynamicArguments()->addDynamicOptions();
    }

    public function handle( Docker $docker ) : void
    {
        passthru( "docker-compose -f {$docker->config()} " . implode( ' ', $this->getParameters() ) );
    }

}
