<?php

namespace Chriha\ProjectCLI\Commands\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class CreateCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'create';

    /** @var string */
    protected $description = 'Create a new project';

    /** @var array */
    protected $types = [
        'django' => 'https://github.com/ProjectCLI/environment-django.git',
        'python' => 'https://github.com/ProjectCLI/environment-python.git',
        'node'   => 'https://github.com/ProjectCLI/environment-node.git',
        'php'    => 'https://github.com/ProjectCLI/environment-php.git',
    ];


    public function configure() : void
    {
        $this->addOption(
            'type',
            null,
            InputOption::VALUE_REQUIRED,
            'Type of the project. Options: ' . implode(', ', array_keys($this->types)),
            'php'
        )
            ->addOption(
                'repository',
                null,
                InputOption::VALUE_REQUIRED,
                'Specify the repository to use as base structure'
            );
        $this->addArgument('directory', InputArgument::REQUIRED, 'Project directory');
    }

    /**
     * Execute the console command.
     *
     * @param Docker $docker
     * @return mixed
     */
    public function handle(Docker $docker) : void
    {
        if (PROJECT_IS_INSIDE) {
            $this->abort('You are currently in a project');
        }

        $repository = $this->repository();
        $directory  = $this->argument('directory');

        if (is_dir($directory)) {
            $this->abort(sprintf("Directory '%s' already exists", $directory));
        }

        $clone = new Process(['git', 'clone', '-q', $repository, $directory]);
        $path  = getcwd() . DS . $directory;

        $this->spinner('Creating project', $clone);

        $envFile = $directory . DS . '.env';

        if (file_exists($envFile . '.example') && ! file_exists($envFile)) {
            copy($envFile . '.example', $envFile);
        }

        Helpers::recursiveRemoveDir($path . DS . '.git');

        $this->spinner('Initializing git', new Process(['git', 'init'], $path));
        $this->info(sprintf("Project '%s' successfully set up", $directory));
    }

    protected function repository() : string
    {
        if ($this->option('repository')) {
            return $this->option('repository');
        } elseif (filter_var($this->option('type'), FILTER_VALIDATE_URL)) {
            return $this->option('type');
        } elseif (in_array($this->option('type'), array_keys($this->types))) {
            return $this->types[$this->option('type')];
        }

        $this->abort(sprintf('Unknown type: %s', $this->option('type')));
    }

    public static function isActive() : bool
    {
        return ! PROJECT_IS_INSIDE;
    }

}
