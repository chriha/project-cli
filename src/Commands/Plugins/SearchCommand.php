<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;

class SearchCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:search';


    protected function configure() : void
    {
        $this->setDescription( 'Search for plugins' );
    }

    public function handle() : void
    {
        //
    }

}
