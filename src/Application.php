<?php

namespace Chriha\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Contracts\Plugin;
use Chriha\ProjectCLI\Libraries\Config\Application as ApplicationConfig;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\NamespaceNotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Application extends \Symfony\Component\Console\Application
{

    private $runningCommand;

    private $defaultCommand;

    private $dispatcher;


    public function doRun( InputInterface $input, OutputInterface $output )
    {
        if ( true === $input->hasParameterOption( [ '--version', '-V' ], true ) )
        {
            $output->writeln( $this->getLongVersion() );

            return 0;
        }

        try
        {
            // Makes ArgvInput::getFirstArgument() able to distinguish an option from an argument.
            $input->bind( $this->getDefinition() );
        }
        catch ( ExceptionInterface $e )
        {
            // Errors must be ignored, full binding/validation happens later when the command is known.
        }

        $name = $this->getCommandName( $input ) ?? 'list';

        if ( ! $name )
        {
            $name       = $this->defaultCommand;
            $definition = $this->getDefinition();
            $definition->setArguments( array_merge(
                $definition->getArguments(),
                [
                    'command' => new InputArgument( 'command', InputArgument::OPTIONAL, $definition->getArgument( 'command' )->getDescription(), $name ),
                ]
            ) );
        }

        try
        {
            $this->runningCommand = null;
            // the command name MUST be the first element of the input
            $command = $this->find( $name );
        }
        catch ( \Throwable $e )
        {
            if ( ! ( $e instanceof CommandNotFoundException && ! $e instanceof NamespaceNotFoundException ) || 1 !== \count( $alternatives = $e->getAlternatives() ) || ! $input->isInteractive() )
            {
                if ( null !== $this->dispatcher )
                {
                    $event = new ConsoleErrorEvent( $input, $output, $e );
                    $this->dispatcher->dispatch( $event, ConsoleEvents::ERROR );

                    if ( 0 === $event->getExitCode() )
                    {
                        return 0;
                    }

                    $e = $event->getError();
                }

                throw $e;
            }

            $alternative = $alternatives[0];

            $style = new SymfonyStyle( $input, $output );
            $style->block( sprintf( "\nCommand \"%s\" is not defined.\n", $name ), null, 'error' );

            if ( ! $style->confirm( sprintf( 'Do you want to run "%s" instead? ', $alternative ), false ) )
            {
                if ( null !== $this->dispatcher )
                {
                    $event = new ConsoleErrorEvent( $input, $output, $e );
                    $this->dispatcher->dispatch( $event, ConsoleEvents::ERROR );

                    return $event->getExitCode();
                }

                return 1;
            }

            $command = $this->find( $alternative );
        }

        $this->runningCommand = $command;
        $exitCode             = $this->doRunCommand( $command, $input, $output );
        $this->runningCommand = null;

        return $exitCode;
    }

    public function configureApp() : void
    {
        Helpers::app()->instance( 'app', $this );

        $this->dispatchErrorEvent();

        $path      = trim( shell_exec( 'git rev-parse --show-toplevel 2>/dev/null' ) );
        $path      = ( empty( $path ) && ! is_dir( getcwd() . DS . 'src' ) ) ? null : $path;
        $inProject = ! ! $path;

        define( 'PROJECT_PATHS_PROJECT', $path ?? '' );
        define( 'PROJECT_IS_INSIDE', $inProject );

        Helpers::app()->instance( 'config', new ApplicationConfig );

        if ( $inProject && ! ! $path
            && file_exists( ( $envPath = Helpers::projectPath( '.env' ) ) ) )
        {
            $dotEnv = new Dotenv();
            $dotEnv->loadEnv( $envPath );
        }
    }

    protected function dispatchErrorEvent() : void
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addListener( ConsoleEvents::ERROR, function( ConsoleErrorEvent $event )
        {
            $event->getOutput()->writeln( sprintf(
                'Oops, exception thrown while running command <info>%s</info>. If you think '
                . PHP_EOL . 'this is a problem with ProjectCLI, please feel free to create an issue at '
                . PHP_EOL . '<comment>https://github.com/chriha/project-cli/issues</comment>',
                $event->getCommand()->getName()
            ) );
        } );

        $this->setDispatcher( $dispatcher );
    }

    public function addProjectCommands() : void
    {
        if ( empty( $path = Helpers::projectPath() ) ) return;

        if ( ! is_dir( "{$path}/commands" ) ) return;

        if ( ! ( $handle = opendir( "{$path}/commands" ) ) ) return;

        $classes = [];

        while ( false !== ( $file = readdir( $handle ) ) )
        {
            if ( $file == "." || $file == ".." ) continue;

            require_once( $path . DS . 'commands' . DS . $file );

            /** @var Command $class */
            $class = "\Project\Commands\\" . rtrim( $file, '.php' );

            // TODO: throw exception
            if ( ! class_exists( $class ) ) continue;

            $classes[] = new $class;
        }

        closedir( $handle );

        $this->addCommands( $classes );
    }

    public function addPluginCommands() : void
    {
        if ( empty( $path = Helpers::home( 'plugins' ) ) ) return;

        if ( ! is_dir( $path ) ) return;

        if ( ! ( $dirHandle = opendir( $path ) ) ) return;

        $classes = [];

        while ( false !== ( $dir = readdir( $dirHandle ) ) )
        {
            if ( $dir == "." || $dir == ".." ) continue;

            if ( ! is_dir( $path . DS . $dir ) ) continue;

            if ( ! ( $fileHandle = opendir( $path . DS . $dir ) ) ) return;

            while ( false !== ( $file = readdir( $fileHandle ) ) )
            {
                if ( $file == "." || $file == ".." ) continue;

                $filePath  = $path . DS . $dir . DS . $file;
                $namespace = Helpers::findNamespace( $filePath );
                /** @var Plugin $class */
                $class = "\\{$namespace}\\" . rtrim( $file, '.php' );

                require_once( $filePath );

                // TODO: throw exception
                if ( ! class_exists( $class ) ) continue;

                $classes[] = new $class;
            }

            closedir( $fileHandle );
        }

        closedir( $dirHandle );

        $this->addCommands( $classes );
    }

}
