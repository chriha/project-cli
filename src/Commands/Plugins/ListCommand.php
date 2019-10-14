<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;

class ListCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:list';

    /** @var string */
    protected $description = 'List all installed plugins';


    public function handle()
    {
        //
    }

}
