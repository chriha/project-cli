<?php

namespace Chriha\ProjectCLI\Commands\Laravel;

use Chriha\ProjectCLI\Commands\Command;

class DumpServerCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'dump-server';

    /** @var string */
    protected $description = 'Start the dump server to collect dump information';

    /** @var bool */
    protected $requiresProject = true;


    public function handle() : void
    {
        $this->call( 'artisan', [ 'dump-server' ] );
    }

}
