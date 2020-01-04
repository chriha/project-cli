<?php

namespace Chriha\ProjectCLI\Commands\Make;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Services\Docker;
use Chriha\ProjectCLI\Services\Git;

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

        $dir = 'plugins' . DS . $namespace . DS . $name;

        if (is_dir($dir)) {
            Helpers::abort('Plugin directory "%s" already exists');
        }

        if ( ! $git->clone($this->repository, $path = Helpers::home($dir))) {
            Helpers::abort('Plugin could not be created');
        }

        Helpers::recursiveRemoveDir($path . DS . '.git');

        $composer = file_get_contents($path . DS . 'composer.json');
        $composer = str_replace('__PLUGIN_NAME__', $name, $composer);
        $composer = str_replace('__NAMESPACE__', $namespace, $composer);
        $composer = str_replace(
            "{$namespace}/{$name}",
            strtolower("{$namespace}/{$name}"),
            $composer
        );

        file_put_contents($path . DS . 'composer.json', $composer);

        $plugin = file_get_contents($path . DS . 'plugin.php');
        $plugin = str_replace('__PLUGIN_NAME__', $name, $plugin);
        $plugin = str_replace('__NAMESPACE__', $namespace, $plugin);

        file_put_contents($path . DS . 'plugin.php', $plugin);

        $class = file_get_contents($path . DS . 'src/Commands/DummyCommand.php');
        $class = str_replace('__PLUGIN_NAME__', $name, $class);
        $class = str_replace('__NAMESPACE__', $namespace, $class);

        file_put_contents($path . DS . 'src/Commands/DummyCommand.php', $class);

        $this->spinner(
            'Installing composer dependencies ...',
            $docker->run(['--rm', '-v', "{$path}:/app", 'composer', 'install'], $path)
        );

        $this->info('Plugin successfully created!');
        $this->warn($path);
    }

}
