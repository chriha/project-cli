<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Services\Git;
use Chriha\ProjectCLI\Services\Plugins\Registry;
use Symfony\Component\Console\Input\InputArgument;

class InstallCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:install';

    /** @var string */
    protected $description = 'Install specified plugins';


    public function configure() : void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the plugin');
    }

    public function handle(Git $git) : void
    {
        $plugins = Helpers::app('plugins') ?? [];
        $plugin  = $this->argument('name');

        if (isset($plugins[$plugin])) {
            $this->abort(sprintf('Plugin <options=bold>%s</> already installed', $plugin));
        }

        dump(Registry::get($plugin));
    }

}
