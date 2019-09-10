<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;

class UninstallCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:uninstall';


    protected function configure() : void
    {
        $this->setDescription( 'Uninstall specified plugins' );
    }

    public function handle() : void
    {
        //
    }

}
