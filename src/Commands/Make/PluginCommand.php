<?php

namespace Chriha\ProjectCLI\Commands\Make;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
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


    public function handle(Git $git) : void
    {
        if ( ! $namespace = trim($this->ask('Specify a namespace'))) {
            $this->abort('Please specify a namespace');
        }

        if ( ! $name = trim($this->ask('Specify a plugin name'))) {
            $this->abort('Please specify a plugin name.');
        }

        $dir = Helpers::pluginsPath(strtolower($namespace . DS . $name));

        if (is_dir($dir)) {
            $this->abort(
                sprintf('Plugin directory "%s" already exists.', $namespace . DS . $name)
            );
        }

        if ( ! $git->clone($this->repository, $dir)) {
            $this->abort('Plugin could not be created.');
        }

        Helpers::rmdir($dir . DS . '.git');

        $configPath    = $dir . DS . 'project.yml';
        $configContent = file_get_contents($configPath);
        $configContent = str_replace('__PLUGIN_NAME__', $name, $configContent);
        $configContent = str_replace('__NAMESPACE__', $namespace, $configContent);

        $config         = Yaml::parse($configContent);
        $config['name'] = strtolower(sprintf('%s/%s', $namespace, $name));

        file_put_contents($configPath, Yaml::dump($config));

        $plugin = file_get_contents($dir . DS . 'plugin.php');
        $plugin = str_replace('__PLUGIN_NAME__', $name, $plugin);
        $plugin = str_replace('__NAMESPACE__', $namespace, $plugin);

        file_put_contents($dir . DS . 'plugin.php', $plugin);

        $class = file_get_contents($dir . DS . 'src/Commands/DummyCommand.php');
        $class = str_replace('__PLUGIN_NAME__', $name, $class);
        $class = str_replace('__NAMESPACE__', $namespace, $class);

        file_put_contents($dir . DS . 'src/Commands/DummyCommand.php', $class);

        $this->info('Plugin successfully created!');
        $this->warn($dir);
        $this->output->writeln(
            'For easier installation in the future, make sure to submit your plugin here:'
            . PHP_EOL . 'https://cli.lazu.io/plugins/submit'
        );
    }

}
