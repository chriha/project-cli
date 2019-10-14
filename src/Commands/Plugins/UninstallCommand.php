<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;

class UninstallCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:uninstall';

    /** @var string */
    protected $description = 'Uninstall specified plugins';


    public function handle() : void
    {
        //
    }

}
