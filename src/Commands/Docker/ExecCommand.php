<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;

class ExecCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'docker:exec';

    /** @var string */
    protected $description = 'Execute commands in running containers';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->addDynamicArguments()->addDynamicOptions();
    }

    public function handle() : void
    {
        $this->call( 'docker:compose', $this->getParameters( [ 'exec' ] ) );
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE;
    }

}
