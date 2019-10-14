<?php

namespace Chriha\ProjectCLI\Commands\Php;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class ComposerCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'composer';

    /** @var string */
    protected $description = 'Run composer commands inside the web container';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->addDynamicArguments()->addDynamicOptions();
    }

    public function handle( Docker $docker ) : void
    {
        passthru( "{$docker->compose()} {$docker->runExec()} composer "
            . implode( ' ', $this->getParameters() ) );
    }

}
