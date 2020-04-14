<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Services\Git;
use Chriha\ProjectCLI\Services\Plugins\Registry;
use Symfony\Component\Console\Input\InputArgument;

class UpdateCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:update';

    /** @var string */
    protected $description = 'Update the specified plugin';


    public function configure() : void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the plugin.');
    }

    public function handle(Git $git) : void
    {
        $plugins = Helpers::app('plugins') ?? [];
        $name    = $this->argument('name');

        if ( ! isset($plugins[$name])) {
            $this->abort(sprintf('Plugin <options=bold>%s</> is not installed', $name));
        }

        $plugin = Registry::get($name);
        $path   = Helpers::pluginsPath($plugin->name);

        if (version_compare($plugins[$name]->version, $plugin->version) >= 0) {
            $this->info('You already have the latest version.');
            exit;
        }

        if ( ! $git->isClean($path)) {
            $this->abort('You have changed files in the plugin directory.');
        }

        if ( ! $git->checkout($plugin->version, $path)) {
            $this->abort('Unable to update plugin. Try again later ...');
        }

        Registry::incrementInstallations($plugin);
        $this->info('Plugin successfully updated!');
    }

}
