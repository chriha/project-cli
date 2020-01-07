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
        $name    = $this->argument('name');

        if (isset($plugins[$name])) {
            $this->abort(sprintf('Plugin <options=bold>%s</> already installed', $name));
        }

        $plugin = Registry::get($name);
        $path   = Helpers::pluginsPath($plugin->name);

        if (!$git->clone($plugin->source, $path)) {
            $this->abort('Unable to download plugin.');
        }

        if( !$git->checkout($plugin->version, $path)) {
            $this->error('Unable to find plugin version. Reverting ...');
            Helpers::rmdir($path);
            exit;
        }

        $this->info('Plugin successfully installed!');
        $plugin->asListItem();
    }

}
