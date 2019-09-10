<?php

namespace Chriha\ProjectCLI\Commands\Php;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;

class TestCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'test';

    /** @var bool */
    protected $requiresProject = true;

    /** @var bool */
    protected $hasDynamicOptions = true;


    public function configure() : void
    {
        $this->setDescription( 'Run unit tests' )
            ->addArgument( 'commands', InputArgument::IS_ARRAY, 'The command you want to execute' );
    }

    public function handle( Docker $docker ) : void
    {
        passthru( "{$docker->compose()} {$docker->runExec( 'test' )} ./vendor/bin/phpunit "
            . implode( ' ', $this->additionalArgs() ) );
    }

}
