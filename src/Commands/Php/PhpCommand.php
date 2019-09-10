<?php

namespace Chriha\ProjectCLI\Commands\Php;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;

class PhpCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'php';

    /** @var bool */
    protected $hasDynamicOptions = true;

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->setDescription( 'Run PHP commands' )
            ->addArgument( 'commands', InputArgument::IS_ARRAY, 'The command you want to execute' );
    }

    public function handle( Docker $docker ) : void
    {
        passthru( "{$docker->compose()} {$docker->runExec()} php "
            . implode( " ", $this->additionalArgs() ) );
    }

}
