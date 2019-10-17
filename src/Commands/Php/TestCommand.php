<?php

namespace Chriha\ProjectCLI\Commands\Php;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class TestCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'test';

    /** @var string */
    protected $description = 'Run unit tests';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->addDynamicArguments()->addDynamicOptions();
    }

    public function handle( Docker $docker ) : void
    {
        $docker->exec( 'test', $this->getParameters( [ './vendor/bin/phpunit' ] ) )
            ->setTty( true )
            ->run( function( $type, $buffer )
            {
                $this->output->write( $buffer );
            } );
    }

}
