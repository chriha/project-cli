<?php

namespace Chriha\ProjectCLI\Commands\Node;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class NodeCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'node';

    /** @var string */
    protected $description = 'Run node commands';


    protected function configure() : void
    {
        $this->addDynamicArguments()->addDynamicOptions();
    }

    public function handle( Docker $docker ) : void
    {
        $arguments = implode( ' ', $this->getParameters() );

        passthru( "{$docker->compose()} {$docker->runExec( 'node' )} node {$arguments}" );
    }

}
