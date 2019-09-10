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
use Symfony\Component\Dotenv\Dotenv;

abstract class Command extends SymfonyCommand
{

    use ReceivesInput, ProvidesOutput;

    /** @var bool */
    protected $requiresProject = false;

    /** @var bool */
    protected $hasDynamicOptions = false;

    /** @var array */
    protected $dynamicOptions = [];

    /** @var OutputInterface */
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
        $this->configureApp();
        $this->configureProject();
        $this->loadProjectCommands();

        if ( ! $output->getFormatter()->hasStyle( 'red' ) )
        {
            $style = new OutputFormatterStyle( 'red' );

            $output->getFormatter()->setStyle( 'red', $style );
        }
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        Helpers::app()->instance( 'input', $this->input = $input );
        Helpers::app()->instance( 'output', $this->output = $output );

        if ( $this->requiresProject && ! Helpers::app( 'project.inside' ) )
        {
            Helpers::abort( "You're not in a project" );
        }

        return Helpers::app()->call( [ $this, 'handle' ] );
    }

    /**
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

    protected function configureApp() : void
    {
        $home = $_SERVER['HOME'] . DS . '.project';

        if ( ! is_dir( $home ) ) mkdir( $home, 700, true );

        Helpers::app()->instance( 'paths.home', $home );
    }

    protected function configureProject() : void
    {
        //$path = trim( shell_exec( 'git rev-parse --show-toplevel 2>/dev/null' ) );
        //$path = ( empty( $path ) && ! is_dir( getcwd() . DS . 'src' ) ) ? null : getcwd();
        //
        //Helpers::app()->instance( 'project.path', $path );
        //Helpers::app()->instance( 'project.inside', (bool)$path );

        if ( Helpers::app( 'project.inside' )
            && ! ! Helpers::projectPath()
            && file_exists( ( $envPath = Helpers::projectPath( '.env' ) ) ) )
        {
            $dotEnv = new Dotenv();
            $dotEnv->loadEnv( $envPath );
        }
    }

    protected function loadProjectCommands() : void
    {
        if ( ! Helpers::app( 'project.inside' ) ) return;

        /**
         * we can't just add the path to the configuration
         * as the namespaces get messed up
         */
        $path = Helpers::projectPath( 'commands' );

        if ( ! file_exists( $path ) ) return;

        if ( is_null( $path ) || ! ( $handle = opendir( $path ) ) ) return;

        $classes = [];

        while ( false !== ( $file = readdir( $handle ) ) )
        {
            if ( $file == "." || $file == ".." ) continue;

            require_once( $path . DS . $file );

            /** @var Plugin $class */
            $class = "\Project\Commands\\" . rtrim( $file, '.php' );

            // TODO: throw exception
            if ( ! class_exists( $class ) ) continue;

            $classes[] = $class;
        }

        closedir( $handle );
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
