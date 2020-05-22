<?php

namespace Chriha\ProjectCLI\Commands\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Libraries\Config\Application;
use Chriha\ProjectCLI\Libraries\Config\Project;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

class ConfigCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'config';

    /** @var string */
    protected $description = 'Get and set config ProjectCLI variables';


    public function configure() : void
    {
        $this->addArgument(
            'entry',
            InputArgument::OPTIONAL,
            'The configuration entry'
        )
            ->addArgument(
                'value',
                InputArgument::OPTIONAL,
                'The new value for the given configuration entry'
            )
            ->addOption(
                'local',
                'l',
                InputOption::VALUE_NONE,
                'List or set project configuration entries only'
            )->addOption(
                'global',
                'g',
                InputOption::VALUE_NONE,
                'List or set global configuration entries only'
            )->addOption(
                'rm',
                null,
                InputOption::VALUE_NONE,
                'Remove specified configuration entry'
            );
    }

    /**
     * @param Application $app
     * @param Project $project
     * @return mixed
     */
    public function handle(Application $app, Project $project) : void
    {
        if ( ! $this->option('local') && ! $this->option('global')) {
            $this->abort('Please choose between local or global configuration');
        }

        $config = $this->option('local') ? $project : $app;
        $entry  = $this->argument('entry');

        if (!$entry) {
            $this->output->writeln(Yaml::dump($config->all(), 6, 2));

            return;
        }

        if ($this->argument('value')) {
            $config->set($entry, $this->argument('value'));
        } elseif ($this->option('rm')) {
            $config->unset($entry)->save();

            $this->info('Entry removed!');

            return;
        }

        $this->output->writeln(Yaml::dump($config->get($entry), 6, 2));
    }

}
