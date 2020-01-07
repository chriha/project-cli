<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Services\Plugins\Plugin;

class ListCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:list';

    /** @var string */
    protected $description = 'List all installed plugins';


    public function handle()
    {
        $plugins = Helpers::app('plugins');

        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            $this->output->writeln(
                "<fg=blue>::</> <options=bold>{$plugin->name}</> "
                . "[{$plugin->tag()}]"
            );
        }
    }

}
