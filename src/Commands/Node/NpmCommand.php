<?php

namespace Chriha\ProjectCLI\Commands\Node;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class NpmCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'npm';


    public function configure() : void
    {
        $this->setDescription( 'Run npm commands' )
            ->addDynamicArguments()->addDynamicOptions();
    }

    public function handle( Docker $docker ) : void
    {
        $arguments = implode( ' ', $this->getParameters() );

        passthru( "{$docker->compose()} {$docker->runExec( 'node' )} npm {$arguments}" );
    }

}
