<?php

namespace Chriha\ProjectCLI\Commands\Laravel;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;

class ArtisanCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'artisan';

    /** @var bool */
    protected $requiresProject = true;

    /** @var bool */
    protected $hasDynamicOptions = true;


    public function configure() : void
    {
        $this->setDescription( 'Run artisan commands inside the web container' )
            ->addArgument( 'commands', InputArgument::IS_ARRAY, 'The command you want to execute' );
    }

    public function handle( Docker $docker ) : void
    {
        passthru( "{$docker->compose()} {$docker->runExec()} php artisan "
            . implode( " ", $this->additionalArgs() ) );
    }

}
