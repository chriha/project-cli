<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Plugins\Plugin;
use Chriha\ProjectCLI\Services\Plugins\Registry;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class SearchCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:search';

    /** @var string */
    protected $description = 'Search for plugins';

    public function configure() : void
    {
        $this->addArgument('query', InputArgument::REQUIRED, 'The search query');
    }

    public function handle() : void
    {
        $this->output->write('Fetching available plugins ...');

        $list = Registry::search($this->argument('query'));

        $this->output->write("\x0D");
        $this->output->write("\x1B[2K");

        if ($list->isEmpty()) {
            $this->warn('No plugins found');
            exit(1);
        }

        $this->blue(sprintf('Found %d %s:', $list->count(), Str::plural('plugin', $list->count())));

        /** @var Plugin $plugin */
        foreach ($list as $plugin) {
            //$plugin->asListItem();
            $plugin->asItem();
        }
    }

}
