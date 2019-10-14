<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class DownCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'down';

    /** @var string */
    protected $description = 'Stop and remove containers, networks, images, and volumes';

    /** @var bool */
    protected $requiresProject = true;

    public function handle( Docker $docker ) : void
    {
        $this->spinner( 'Shutting down project', $docker->process( [ 'down' ] ) );
    }

}
