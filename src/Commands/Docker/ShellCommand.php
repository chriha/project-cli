<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;

class ShellCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'shell';

    /** @var string */
    protected $description = 'Get a shell for a docker service';

    /** @var bool */
    protected $requiresProject = true;

    public function configure() : void
    {
        $this->addArgument(
            'service',
            InputArgument::OPTIONAL,
            'Name of the docker-compose service',
            'web'
        );
    }

    public function handle() : void
    {
        $this->call('docker:exec', [$this->argument('service'), 'bash']);
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE;
    }

}
