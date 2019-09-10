<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;

class ListCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:list';


    protected function configure() : void
    {
        $this->setDescription( 'List all installed plugins' );
    }

    public function handle()
    {
        //
    }

}
