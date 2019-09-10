<?php

namespace Chriha\ProjectCLI\Commands\Node;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;

class NodeCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'node';

    /** @var bool */
    protected $hasDynamicOptions = true;


    protected function configure() : void
    {
        $this->setDescription( 'Run node commands' )
            ->addArgument( 'commands', InputArgument::IS_ARRAY, 'The command you want to execute' );
    }

    public function handle( Docker $docker ) : void
    {
        $arguments = implode( ' ', $this->argument( 'commands' ) );
        $options   = implode( ' ', $this->additionalArgs() );

        passthru( "{$docker->compose()} {$docker->runExec( 'node' )} node {$arguments} {$options}" );
    }

}
