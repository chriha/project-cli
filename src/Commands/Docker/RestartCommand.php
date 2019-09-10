<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;

class RestartCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'restart';

    /** @var bool */
    protected $requiresProject = true;


    protected function configure() : void
    {
        $this->setDescription( 'Remove and re-create the containers' );
    }

    public function handle()
    {
        $this->call( 'down' );
        $this->call( 'up' );
    }

}
