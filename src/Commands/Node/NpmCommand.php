<?php

namespace Chriha\ProjectCLI\Commands\Node;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class NpmCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'npm';

    /** @var string */
    protected $description = 'Run npm commands';


    public function configure() : void
    {
        $this->addDynamicArguments()->addDynamicOptions();
    }

    public function handle( Docker $docker ) : void
    {
        $docker->exec( 'node', $this->getParameters( [ 'npm' ] ) )
            ->setTty( true )
            ->run( function( $type, $buffer )
            {
                $this->output->write( $buffer );
            } );
    }

}
