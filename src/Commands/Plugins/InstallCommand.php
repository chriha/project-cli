<?php

namespace Chriha\ProjectCLI\Commands\Plugins;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Exceptions\Plugins\NotFoundException;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Libraries\Config\Project;
use Chriha\ProjectCLI\Services\Git;
use Chriha\ProjectCLI\Services\Plugins\Plugin;
use Chriha\ProjectCLI\Services\Plugins\Registry;
use Exception;
use Symfony\Component\Console\Input\InputArgument;

class InstallCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'plugins:install';

    /** @var string */
    protected $description = 'Install specified plugins';


    public function configure() : void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the plugin');
    }

    public function handle(Git $git) : void
    {
        $plugins = Helpers::app('plugins') ?? [];
        $name    = $this->argument('name');

        if ( ! $this->argument('name')) {
            $missing = $this->checkForRequiredPlugins($plugins);

            if (empty($missing)) {
                $this->warn('No missing plugins found.');

                return;
            }

            foreach ($missing as $name) {
                try {
                    $plugin = Registry::get($name);
                    $this->install($git, $plugin);
                    $this->info(
                        sprintf('Plugin <options=bold>%s</> successfully installed!', $name)
                    );
                } catch (NotFoundException $exception) {
                    $this->error(
                        sprintf('Required plugin <options=bold>%s</> does not exist', $name)
                    );
                } catch (Exception $exception) {
                    $this->logger->error($exception);
                    $this->error(
                        sprintf('Required plugin <options=bold>%s</> could not be installed', $name)
                    );
                }
            }

            exit;
        }

        if (isset($plugins[$name])) {
            $this->abort(sprintf('Plugin <options=bold>%s</> already installed', $name));
        }

        $plugin = Registry::get($name);
        $this->install($git, $plugin);
        $this->info(sprintf('Plugin <options=bold>%s</> successfully installed!', $name));
    }

    protected function install(Git $git, Plugin $plugin) : void
    {
        $path = Helpers::pluginsPath($plugin->name);

        if ( ! $git->clone($plugin->source, $path)) {
            $this->abort('Unable to download plugin.');
        }

        if ( ! $git->checkout($plugin->version, $path)) {
            Helpers::rmdir($path);
            $this->abort('Unable to find plugin version. Reverting ...');
        }
    }

    protected function checkForRequiredPlugins(array $available) : array
    {
        $config  = new Project();
        $missing = [];

        if ( ! $config->hasConfig() || empty($plugins = $config->get('require'))) {
            return [];
        }

        foreach ($plugins as $name) {
            if (isset($available[$name])) {
                continue;
            }

            $missing[] = $name;
        }

        return $missing;
    }

}
