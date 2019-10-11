<?php

namespace Chriha\ProjectCLI\Commands\Php;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class TestCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'test';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->setDescription( 'Run unit tests' )
            ->addDynamicArguments()->addDynamicOptions();
    }

    public function handle( Docker $docker ) : void
    {
        passthru( "{$docker->compose()} {$docker->runExec( 'test' )} ./vendor/bin/phpunit "
            . implode( ' ', $this->getParameters() ) );
    }

}
