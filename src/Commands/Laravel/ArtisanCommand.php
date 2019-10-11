<?php

namespace Chriha\ProjectCLI\Commands\Laravel;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class ArtisanCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'artisan';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->setDescription( 'Run artisan commands inside the web container' )
            ->addDynamicArguments()->addDynamicOptions();
    }

    public function handle( Docker $docker ) : void
    {
        passthru( "{$docker->compose()} {$docker->runExec()} php artisan "
            . implode( ' ', $this->getParameters() ) );
    }

}
