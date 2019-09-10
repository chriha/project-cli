<?php

namespace Chriha\ProjectCLI;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\NamespaceNotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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

    public function addProjectCommands() : void
    {
        $path      = trim( shell_exec( 'git rev-parse --show-toplevel 2>/dev/null' ) );
        $path      = ( empty( $path ) && ! is_dir( getcwd() . DS . 'src' ) ) ? null : $path;
        $inProject = ! ! $path;

        Helpers::app()->instance( 'project.path', $path ?? '' );
        Helpers::app()->instance( 'project.inside', $inProject );

        if ( empty( $path ) ) return;

        if ( ! is_dir( "{$path}/commands" ) ) return;

        if ( ! ( $handle = opendir( "{$path}/commands" ) ) ) return;

        $classes = [];

        while ( false !== ( $file = readdir( $handle ) ) )
        {
            if ( $file == "." || $file == ".." ) continue;

            require_once( $path . DS . 'commands' . DS . $file );

            /** @var Plugin $class */
            $class = "\Project\Commands\\" . rtrim( $file, '.php' );

            // TODO: throw exception
            if ( ! class_exists( $class ) ) continue;

            $classes[] = new $class;
        }

        closedir( $handle );

        $this->addCommands( $classes );
    }

}
