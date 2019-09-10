<?php

namespace Chriha\ProjectCLI\Commands\Laravel;

use Chriha\ProjectCLI\Commands\Command;

class DumpServerCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'dump-server';

    /** @var bool */
    protected $requiresProject = true;


    protected function configure() : void
    {
        $this->setDescription( 'Start the dump server to collect dump information' );
    }

    public function handle() : void
    {
        $this->call( 'artisan', [ 'commands' => [ 'dump-server' ] ] );
    }

}
