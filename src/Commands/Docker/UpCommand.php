<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class UpCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'up';

    /** @var string */
    protected $description = 'Create and start project containers';

    /** @var bool */
    protected $requiresProject = true;


    protected function configure() : void
    {
        $this->addDynamicArguments()->addDynamicOptions();
    }

    public function handle(Docker $docker) : void
    {
        if ( ! empty($blocked = $docker->hasOccupiedPorts())) {
            $this->abort("Ports are already occupied: " . implode(', ', $blocked));
        }

        $process = $docker->process($this->getParameters(['up', '-d']));

        $this->spinner('Starting services', $process);
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE;
    }

}
