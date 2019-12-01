<?php

namespace Chriha\ProjectCLI\Commands\Laravel;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Services\Docker;

class ArtisanCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'artisan';

    /** @var string */
    protected $description = 'Run artisan commands inside the web container';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->addDynamicArguments()->addDynamicOptions();
    }

    public function handle(Docker $docker) : void
    {
        $docker->exec('web', $this->getParameters(['php', 'artisan']))
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
            && file_exists(Helpers::projectPath('src/artisan'));
    }

}
