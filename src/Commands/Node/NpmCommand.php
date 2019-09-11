<?php

namespace Chriha\ProjectCLI\Commands\Node;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;

class NpmCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'npm';


    public function configure() : void
    {
        $this->setDescription( 'Run npm commands' )
            ->addArgument( 'commands', InputArgument::IS_ARRAY, 'The command you want to execute' );
    }

    public function handle( Docker $docker ) : void
    {
        $arguments = implode( ' ', $this->additionalArgs() );

        passthru( "{$docker->compose()} {$docker->runExec( 'node' )} npm {$arguments}" );
    }

}
