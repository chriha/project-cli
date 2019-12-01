<?php

namespace Chriha\ProjectCLI\Commands\Laravel;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;

class DumpServerCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'laravel:dump-server';

    /** @var string */
    protected $description = 'Start the dump server to collect dump information';

    /** @var bool */
    protected $requiresProject = true;


    public function handle() : void
    {
        $this->call('artisan', ['dump-server']);
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE
            && file_exists(Helpers::projectPath('src/artisan'));
    }

}
