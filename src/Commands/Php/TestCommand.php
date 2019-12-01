<?php

namespace Chriha\ProjectCLI\Commands\Php;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Services\Docker;

class TestCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'php:test';

    /** @var string */
    protected $description = 'Run unit tests';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->addDynamicArguments()->addDynamicOptions();
    }

    public function handle(Docker $docker) : void
    {
        $docker->exec('test', $this->getParameters(['./vendor/bin/phpunit']))
            ->setTty(true)
            ->run(
                function ($type, $buffer)
                {
                    $this->output->write($buffer);
                }
            );
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE
            && file_exists(Helpers::projectPath('src/vendor/bin/phpunit'));
    }

}
