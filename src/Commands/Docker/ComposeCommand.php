<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class ComposeCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'docker:compose';

    /** @var string */
    protected $description = 'Run docker-compose commands';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->addDynamicArguments()->addDynamicOptions();
    }

    public function handle(Docker $docker) : void
    {
        $docker->process($this->getParameters())->setTty(true)
            ->run(
                function ($type, $buffer)
                {
                    $this->output->write($buffer);
                }
            );
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE;
    }

}
