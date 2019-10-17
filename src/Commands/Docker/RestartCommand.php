<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;

class RestartCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'restart';

    /** @var string */
    protected $description = 'Remove and re-create the containers';

    /** @var bool */
    protected $requiresProject = true;

    public function handle()
    {
        $this->call( 'down' );
        $this->call( 'up' );
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE;
    }

}
