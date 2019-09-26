<?php

namespace Chriha\ProjectCLI\Commands;

use Chriha\ProjectCLI\Helpers;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    /** @var OutputStyle */
    public $output;

    /** @var InputInterface */
    public $input;

    /** @var bool */
    public $inProject = false;


    public function __construct( string $name = null )
    {
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
     * @see InputInterface::bind()
     * @see InputInterface::validate()
     */
    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        if ( ! $output->getFormatter()->hasStyle( 'red' ) )
        {
            $style = new OutputFormatterStyle( 'red' );

            $output->getFormatter()->setStyle( 'red', $style );
        }
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        Helpers::app()->instance( 'input', $this->input = $input );
        Helpers::app()->instance( 'output', $this->output = new SymfonyStyle( $input, $output ) );

        if ( $this->requiresProject && ! Helpers::app( 'project.inside' ) )
        {
            Helpers::abort( "You're not in a project" );
        }

        return Helpers::app()->call( [ $this, 'handle' ] );
    }

    /**
     * @param int|null $index
     * @return array
     * @todo: use $dynamicOptions instead
     */
    public function additionalArgs( int $index = null ) : array
    {
        $args   = $_SERVER['argv'];
        $search = $index ?? array_search( $this->getName(), $args, true );

        foreach ( $args as $key => $arg )
        {
            if ( $key > $search ) break;

            unset( $args[$key] );
        }

        return $args;
    }

    public function call( $command, array $arguments = [] ) : int
    {
        $arguments['command'] = $command;

        return $this->getApplication()->find( $command )->run(
            new ArrayInput( $arguments ), Helpers::app( 'output' )
        );
    }

    protected function prepareForDynamicOptions()
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

                $this->addOption( new InputOption( $name, null, InputOption::VALUE_OPTIONAL ) );

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

}
