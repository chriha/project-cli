<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Symfony\Component\Console\Input\InputArgument;

class UninstallCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:uninstall';

    /** @var string */
    protected $description = 'Uninstall specified plugin';


    public function configure() : void
    {
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'The name of the plugin you would like to uninstall'
        );
    }

    public function handle() : void
    {
        $plugins = Helpers::app('plugins') ?? [];
        $name    = $this->argument('name');
        $path    = Helpers::pluginsPath($name);

        if ( ! isset($plugins[$name]) && ! is_dir($path)) {
            $this->abort(sprintf('Plugin <options=bold>%s</> is not installed', $name));
        }

        if ( ! Helpers::rmdir($path)) {
            $this->abort('Unable to uninstall plugin.');
        }

        $this->info('Plugin successfully uninstalled!');
    }

}
