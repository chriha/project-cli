<?php

namespace Chriha\ProjectCLI\Commands\Make;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Services\Docker;
use Chriha\ProjectCLI\Services\Git;
use Symfony\Component\Yaml\Yaml;

class PluginCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'make:plugin';

    /** @var string */
    protected $description = 'Create a new ProjectCLI plugin';

    /** @var string */
    protected $repository = 'https://github.com/chriha/project-cli-plugin.git';


    public function handle(Git $git, Docker $docker) : void
    {
        if ( ! $namespace = trim($this->ask('Specify a namespace'))) {
            Helpers::abort('Please specify a namespace');
        }

        if ( ! $name = trim($this->ask('Specify a plugin name'))) {
            Helpers::abort('Please specify a plugin name');
        }

        $dir = strtolower('plugins' . DS . $namespace . DS . $name);

        if (is_dir($dir)) {
            Helpers::abort('Plugin directory "%s" already exists');
        }

        if ( ! $git->clone($this->repository, $path = Helpers::home($dir))) {
            Helpers::abort('Plugin could not be created');
        }

        Helpers::recursiveRemoveDir($path . DS . '.git');

        $configPath    = $path . DS . 'project.yml';
        $configContent = file_get_contents($configPath);
        $configContent = str_replace('__PLUGIN_NAME__', $name, $configContent);
        $configContent = str_replace('__NAMESPACE__', $namespace, $configContent);

        $config         = Yaml::parse($configContent);
        $config['name'] = strtolower(sprintf('%s/%s', $name, $namespace));

        file_put_contents($configPath, Yaml::dump($config));

        $plugin = file_get_contents($path . DS . 'plugin.php');
        $plugin = str_replace('__PLUGIN_NAME__', $name, $plugin);
        $plugin = str_replace('__NAMESPACE__', $namespace, $plugin);

        file_put_contents($path . DS . 'plugin.php', $plugin);

        $class = file_get_contents($path . DS . 'src/Commands/DummyCommand.php');
        $class = str_replace('__PLUGIN_NAME__', $name, $class);
        $class = str_replace('__NAMESPACE__', $namespace, $class);

        file_put_contents($path . DS . 'src/Commands/DummyCommand.php', $class);

        $this->info('Plugin successfully created!');
        $this->warn($path);
    }

}
