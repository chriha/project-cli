<?php

namespace Chriha\ProjectCLI\Commands\Php;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;

class ComposerCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'composer';

    /** @var bool */
    protected $hasDynamicOptions = true;

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->setDescription( 'Run composer commands inside the web container' )
            ->addArgument( 'commands', InputArgument::IS_ARRAY, 'The command you want to execute' );
    }

    public function handle( Docker $docker ) : void
    {
        passthru( "{$docker->compose()} {$docker->runExec()} composer "
            . implode( ' ', $this->additionalArgs() ) );
    }

}
