<?php

namespace Chriha\ProjectCLI\Commands\Node;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class NodeCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'node';


    protected function configure() : void
    {
        $this->setDescription( 'Run node commands' )
            ->addDynamicArguments()->addDynamicOptions();
    }

    public function handle( Docker $docker ) : void
    {
        $arguments = implode( ' ', $this->getParameters() );

        passthru( "{$docker->compose()} {$docker->runExec( 'node' )} node {$arguments}" );
    }

}
