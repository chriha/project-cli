<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;

class InstallCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:install';


    protected function configure() : void
    {
        $this->setDescription( 'Install specified plugins' );
    }

    public function handle() : void
    {
        //
    }

}
