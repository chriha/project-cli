<?php

namespace Chriha\ProjectCLI\Commands;

use Chriha\ProjectCLI\Console\Input\ArrayInput;
use Chriha\ProjectCLI\Console\Output\ProjectStyle;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Traits\ProvidesOutput;
use Chriha\ProjectCLI\Traits\ReceivesInput;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class Command extends SymfonyCommand
{

    use ReceivesInput, ProvidesOutput;

    /** @var bool */
    protected $requiresProject = false;

    /** @var bool */
    protected $hasDynamicOptions = false;

    /** @var array */
    protected $dynamicOptions = [];

    /** @var string */
    protected $description;

    /** @var OutputStyle */
    public $output;

    /** @var InputInterface */
    public $input;

    /** @var LoggerInterface */
    protected $logger;


    public function __construct( string $name = null )
    {
        $this->setDescription( $this->description );

        parent::__construct( $name );

        if ( ! $this->hasDynamicOptions ) return;

        $this->prepareForDynamicOptions();
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws BindingResolutionException
     * @see InputInterface::bind()
     * @see InputInterface::validate()
     */
    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        $this->logger = Helpers::logger();

        if ( ! $output->getFormatter()->hasStyle( 'red' ) )
        {
            $style = new OutputFormatterStyle( 'red' );

            $output->getFormatter()->setStyle( 'red', $style );
        }
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        Helpers::app()->instance( 'input', $this->input = $input );
        Helpers::app()->instance( 'output', $this->output = new ProjectStyle( $input, $output ) );

        if ( $this->requiresProject && ! PROJECT_IS_INSIDE )
        {
            Helpers::abort( "You're not in a project" );
        }

        return Helpers::app()->call( [ $this, 'handle' ] );
    }

    /**
     * Call another command
     *
     * @param $command
     * @param array $arguments
     * @param bool $showOutput
     * @return int
     * @throws BindingResolutionException
     */
    public function call( $command, array $arguments = [], bool $showOutput = true ) : int
    {
        $arguments['command'] = $command;

        $output = $showOutput ? Helpers::app( 'output' ) : new NullOutput;

        return $this->getApplication()->find( $command )->run(
            new ArrayInput( $arguments ), $output
        );
    }

    /**
     * Prepare the application for dynamic options
     * by allowing options provided to the command
     *
     * @return void
     */
    protected function prepareForDynamicOptions() : void
    {
        $this->setDefinition( new class( $this->getDefinition(), $this->dynamicOptions ) extends InputDefinition
        {

            protected $dynamicOptions = [];

            public function __construct( InputDefinition $definition, array &$dynamicOptions )
            {
                parent::__construct();

                $this->setArguments( $definition->getArguments() );
                $this->setOptions( $definition->getOptions() );

                $this->dynamicOptions =& $dynamicOptions;
            }

            public function getOption( $name )
            {
                if ( parent::hasOption( $name ) )
                {
                    return parent::getOption( $name );
                }
                elseif ( ! parent::hasOption( '*' ) )
                {
                    throw new InvalidArgumentException(
                        sprintf( 'The "--%s" option does not exist.', $name )
                    );
                }

                $this->addOption(
                    new InputOption( $name, null, InputOption::VALUE_OPTIONAL, '', true )
                );

                $this->dynamicOptions[] = $name;

                return parent::getOption( $name );
            }

            public function hasOption( $name ) : bool
            {
                return true;
            }

            public function hasShortcut( $shortcut ) : bool
            {
                return true;
            }

            public function getOptionForShortcut( $shortcut )
            {
                if ( parent::hasShortcut( $shortcut ) )
                {
                    return parent::getOptionForShortcut( $shortcut );
                }

                $this->addOption( new InputOption( "-{$shortcut}", $shortcut, InputOption::VALUE_OPTIONAL ) );

                return parent::getOptionForShortcut( $shortcut );
            }
        } );
    }

    /**
     * Allow dynamic arguments for the command
     *
     * @return Command
     */
    public function addDynamicArguments() : self
    {
        return $this->addArgument( '*', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Arguments for the specified command' );
    }

    /**
     * Allow dynamic options for the command
     *
     * @return Command
     */
    public function addDynamicOptions() : self
    {
        $this->hasDynamicOptions = true;

        return $this->addOption( '*', null, InputOption::VALUE_OPTIONAL, 'Options for the specified command' );
    }

}
