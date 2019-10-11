<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class StatusCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'status';

    /** @var bool */
    protected $requiresProject = true;


    protected function configure() : void
    {
        $this->setDescription( 'List all service containers and show their status' );
    }

    public function handle( Docker $docker )
    {
        $process = $docker->process( [ 'ps' ] );
        $process->run();

        if ( ! $process->isSuccessful() )
        {
            $this->abort( $process->getErrorOutput() );
        }

        echo $process->getOutput();
    }

}
