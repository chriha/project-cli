<?php

namespace Chriha\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Console\Input\ArgvInput;
use Chriha\ProjectCLI\Console\Output\ProjectStyle;
use Chriha\ProjectCLI\Libraries\Config\Application as ApplicationConfig;
use Chriha\ProjectCLI\Libraries\Config\Project;
use Chriha\ProjectCLI\Services\Plugins\Plugin;
use Exception;
use Illuminate\Support\Str;
use PHLAK\SemVer\Version;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\NamespaceNotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class Application extends \Symfony\Component\Console\Application
{

    private $runningCommand;

    private $defaultCommand;

    /** @var EventDispatcher */
    private $dispatcher;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $plugins = [];

    /** @var array */
    public const LEVEL_VERBOSITY = [
        LogLevel::ALERT   => OutputInterface::VERBOSITY_VERBOSE,
        LogLevel::WARNING => OutputInterface::VERBOSITY_VERBOSE,
        LogLevel::INFO    => OutputInterface::VERBOSITY_NORMAL,
    ];

    /** @var array */
    public const LEVEL_FORMAT = [
        LogLevel::NOTICE  => 'options=bold',
        LogLevel::ERROR   => 'red',
        LogLevel::ALERT   => 'red',
        LogLevel::WARNING => 'comment',
        LogLevel::DEBUG   => 'comment',
    ];

    /** @var array */
    private $missing = [];


    /**
     * Runs the current application.
     *
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     * @return int 0 if everything went fine, or an error code
     *
     * @throws Throwable
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }

        return parent::run($input, $output);
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger(
            $output,
            static::LEVEL_VERBOSITY,
            static::LEVEL_FORMAT
        );

        Helpers::app()->instance('logger', $this->logger);
        Helpers::app()->instance('input', $input);
        Helpers::app()->instance('output', new ProjectStyle($input, $output));

        if (true === $input->hasParameterOption(['--version', '-V'], true)
            && count($_SERVER['argv']) === 2) {
            $output->writeln($this->getLongVersion());

            return 0;
        }

        try {
            // Makes ArgvInput::getFirstArgument() able to distinguish an option from an argument.
            $input->bind($this->getDefinition());
        } catch (ExceptionInterface $e) {
            // Errors must be ignored, full binding/validation happens later when the command is known.
        }

        $name = $this->getCommandName($input) ?? 'list';

        if ( ! $name) {
            $name       = $this->defaultCommand;
            $definition = $this->getDefinition();
            $definition->setArguments(
                array_merge(
                    $definition->getArguments(),
                    [
                        'command' => new InputArgument(
                            'command',
                            InputArgument::OPTIONAL,
                            $definition->getArgument(
                                'command'
                            )->getDescription(),
                            $name
                        ),
                    ]
                )
            );
        }

        try {
            $this->runningCommand = null;
            // the command name MUST be the first element of the input
            $command = $this->find($name);
        } catch (Throwable $e) {
            if ( ! ($e instanceof CommandNotFoundException && ! $e instanceof NamespaceNotFoundException) || 1 !== \count(
                    $alternatives = $e->getAlternatives()
                ) || ! $input->isInteractive()) {
                if (null !== $this->dispatcher) {
                    $event = new ConsoleErrorEvent($input, $output, $e);
                    $this->dispatcher->dispatch($event, ConsoleEvents::ERROR);

                    if (0 === $event->getExitCode()) {
                        return 0;
                    }

                    $e = $event->getError();
                }

                throw $e;
            }

            $alternative = $alternatives[0];

            $style = new ProjectStyle($input, $output);
            $style->block(
                sprintf("\nCommand \"%s\" is not defined.\n", $name),
                null,
                'error'
            );

            if ( ! $style->confirm(
                sprintf('Do you want to run "%s" instead? ', $alternative),
                false
            )) {
                if (null !== $this->dispatcher) {
                    $event = new ConsoleErrorEvent($input, $output, $e);
                    $this->dispatcher->dispatch($event, ConsoleEvents::ERROR);

                    return $event->getExitCode();
                }

                return 1;
            }

            $command = $this->find($alternative);
        }

        $this->runningCommand = $command;
        $exitCode             = $this->doRunCommand($command, $input, $output);
        $this->runningCommand = null;

        if ( ! empty($this->missing)) {
            $output->writeln('');
            $text   = 'Required plugins';
            $length = Str::length(strip_tags($text)) + 24;

            $output->writeln('<comment>' . str_repeat('*', $length) . '</>');
            $output->writeln(
                '<comment>' . '*           ' . $text . '           *' . '</>'
            );
            $output->writeln('<comment>' . str_repeat('*', $length) . '</>');

            foreach ($this->missing as $plugin) {
                $output->writeln('- ' . $plugin);
            }

            $output->writeln('');
        }

        $this->checkForNewRelease();

        return $exitCode;
    }

    public function configureApp() : void
    {
        Helpers::app()->instance('app', $this);

        $this->addEventListeners();

        $path      = trim(shell_exec('git rev-parse --show-toplevel 2>/dev/null'));
        $path      = (empty($path) && ! is_dir(getcwd() . DS . 'src')) ? null : $path;
        $inProject = ! ! $path;

        define('PROJECT_PATHS_PROJECT', $path ?? '');
        define('PROJECT_IS_INSIDE', $inProject);

        Helpers::app()->instance('config', new ApplicationConfig());

        if ($inProject && ! ! $path
            && file_exists(($envPath = Helpers::projectPath('.env')))) {
            $dotEnv = new Dotenv();
            $dotEnv->loadEnv($envPath);
        }
    }

    /**
     * Adds an array of command objects.
     *
     * If a Command is not enabled it will not be added.
     *
     * @param \Symfony\Component\Console\Command\Command[] $commands An array of commands
     */
    public function addCommands(array $commands)
    {
        /** @var Command $command */
        foreach ($commands as $command) {
            if (method_exists($command, 'isActive') && ! $command::isActive()) {
                continue;
            }

            $this->add($command);
        }
    }

    public function addProjectCommands() : void
    {
        if (empty($path = Helpers::projectPath())) {
            return;
        }

        if ( ! is_dir("{$path}/commands")) {
            return;
        }

        if ( ! ($handle = opendir("{$path}/commands"))) {
            return;
        }

        $classes = [];

        while (false !== ($file = readdir($handle))) {
            if ($file == "." || $file == "..") {
                continue;
            }

            require_once($path . DS . 'commands' . DS . $file);

            /** @var Command $class */
            $class = "\Project\Commands\\" . rtrim($file, '.php');

            // TODO: throw exception
            if ( ! class_exists($class)) {
                continue;
            }

            $classes[] = new $class();
        }

        closedir($handle);

        $this->addCommands($classes);
    }

    public function addPluginCommands() : void
    {
        if (empty($path = Helpers::pluginsPath()) || ! is_dir($path)) {
            mkdir($path);
        }

        if ( ! ($dirHandle = opendir($path))) {
            return;
        }

        $this->plugins = $commands = [];

        // looping through ~/.project/plugins/...
        while (false !== ($namespace = readdir($dirHandle))) {
            if ( ! ($namespaceHandle = $this->subdirectoryHandle(
                $path . DS . $namespace
            ))) {
                continue;
            }

            // looping through ~/.project/plugins/NAMESPACE/...
            while (false !== ($pluginName = readdir($namespaceHandle))) {
                try {
                    $pluginPath = $path . DS . $namespace . DS . $pluginName;

                    if ( ! ($fileHandle = $this->subdirectoryHandle($pluginPath))) {
                        continue;
                    }

                    $pluginFile = $pluginPath . DS . 'plugin.php';
                    $configFile = $pluginPath . DS . 'project.yml';
                    $config     = Yaml::parseFile($configFile);

                    if ( ! isset($config['name'])) {
                        continue;
                    }

                    require_once $pluginFile;

                    if (is_null(
                            $config['commands'] ?? null
                        ) || empty($config['commands'])) {
                        continue;
                    }

                    $this->plugins[$config['name']] = new Plugin(
                        [
                            'name'     => $config['name'],
                            'commands' => $config['commands'],
                            'version'  => $config['version'] ?? null,
                        ]
                    );

                    foreach ($config['commands'] as $command) {
                        if ( ! (new $command()) instanceof Command) {
                            continue;
                        }

                        $commands[] = new $command();
                    }
                } catch (Exception $e) {
                    // ignore plugin
                }
            }

            closedir($namespaceHandle);
        }

        Helpers::app()->instance('plugins', $this->plugins);

        closedir($dirHandle);

        if (PROJECT_IS_INSIDE) {
            $this->checkForRequiredPlugins();
        }

        $this->addCommands($commands);
    }

    protected function checkForRequiredPlugins() : void
    {
        $config = new Project();

        if ( ! $config->hasConfig() || empty($plugins = $config->get('require'))) {
            return;
        }

        foreach ($plugins as $name) {
            if (isset($this->plugins[$name])) {
                continue;
            }

            $this->missing[] = $name;
        }
    }

    protected function checkForNewRelease() : void
    {
        $checkedAt = Helpers::app('config')->get('version_checked_at');

        if ( ! $checkedAt || ! is_int($checkedAt)) {
            $checkedAt = time() - 86400;
        }

        // was last check more than 24hrs ago
        if ((time() - $checkedAt) < 86400) {
            return;
        }

        $output = Helpers::app('output');

        $output->writeln('');
        $output->write('<comment>Checking for new version ... </comment>');

        $release = Helpers::latestRelease();
        $current = new Version(Helpers::app('app')->getVersion());

        if ($release && $release->gt($current)) {
            $output->writeln('');
            $output->writeln('');
            $text   = 'New version available: ' . $release->prefix();
            $length = Str::length(strip_tags($text)) + 12;

            $output->writeln('<fg=blue>' . str_repeat('*', $length) . '</>');
            $output->writeln('<fg=blue>' . '*     ' . $text . '     *' . '</>');
            $output->writeln('<fg=blue>' . str_repeat('*', $length) . '</>');

            foreach ($this->missing as $plugin) {
                $output->writeln('- ' . $plugin);
            }

            $output->writeln('');
        } else {
            $output->writeln("<fg=green>done</>");
        }

        Helpers::app('config')->set('version_checked_at', time())->save();
    }

    public function __destruct()
    {
        $time = round((microtime(true) - PROJECT_START) * 1000);

        if ( ! $this->logger) {
            return;
        }

        $this->logger->debug('Overall runtime: ' . $time . 'ms');
        $this->logger->debug(
            'Memory allocated: ' . round(memory_get_usage(true) / 1000000, 2) . 'MB'
        );
    }

    /**
     * @param string|null $dir
     * @return bool
     */
    private function subdirectoryHandle(?string $dir)
    {
        if (is_null($dir) || substr($dir, -1) === '.' || ! is_dir($dir)) {
            return false;
        }

        return opendir($dir);
    }

    private function addEventListeners() : void
    {
        $dispatcher = new EventDispatcher();

        //$dispatcher->addListener(
        //    ConsoleEvents::COMMAND,
        //    function (ConsoleCommandEvent $event)
        //    {
        //        $input   = $event->getInput();
        //        $output  = $event->getOutput();
        //        $command = $event->getCommand();
        //        $output->writeln(
        //            sprintf('Before running command <info>%s</info>', $command->getName())
        //        );
        //        $application = $command->getApplication();
        //    }
        //);

        $dispatcher->addListener(
            ConsoleEvents::ERROR,
            function (ConsoleErrorEvent $event)
            {
                $event->getOutput()->writeln(
                    sprintf(
                        'Oops, exception thrown while running command <info>%s</info>. If you think '
                        . PHP_EOL . 'this is a problem with ProjectCLI, please feel free to create an issue at '
                        . PHP_EOL . '<comment>https://github.com/chriha/project-cli/issues</comment>',
                        $event->getCommand()->getName()
                    )
                );
            }
        );

        $this->setDispatcher($dispatcher);
    }

}
