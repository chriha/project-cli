<?php

namespace Chriha\ProjectCLI\Commands\Docker;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;

class TopCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'docker:top';

    /** @var string */
    protected $description = 'Display a live stream of container(s) resource usage statistics';

    /** @var bool */
    protected $requiresProject = true;

    public function handle( Docker $docker )
    {
        passthru( "docker-compose -f {$docker->config()} ps | grep 'Up\|Exit' | awk '{print $1}' | tr \"\\n\" \" \" | xargs docker stats --all --format \"table {{.Name}}\t{{.CPUPerc}}\t{{.MemPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}\"" );
    }

}
