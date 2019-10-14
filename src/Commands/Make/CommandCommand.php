<?php

namespace Chriha\ProjectCLI\Commands\Make;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CommandCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'make:command';

    /** @var string */
    protected $description = 'Create a command for your project';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->addArgument( 'cmd', InputArgument::REQUIRED, 'The name of the command' )
            ->addOption( 'command', null, InputOption::VALUE_REQUIRED );
    }

    public function handle() : void
    {
        $stub  = __DIR__ . DS . '..' . DS . '..' . DS . 'Stubs' . DS . 'command.stub';
        $cmd   = $this->argument( 'cmd' );
        $last  = strrpos( $cmd, '/' );
        $class = substr( $cmd, $last );
        $file  = Helpers::projectPath( 'commands' . DS . $cmd . '.php' );

        if ( file_exists( $file ) )
        {
            $this->abort( 'Command with this name already exists!' );
        }

        @mkdir( Helpers::projectPath( 'commands' ), 0755, true );

        $content = file_get_contents( $stub );
        $content = str_replace( 'DummyClass', $class, $content );

        if ( $this->option( 'command' ) )
        {
            $content = str_replace( 'dummy:command', $this->option( 'command' ), $content );
        }

        file_put_contents( $file, $content );

        $this->info( 'Command successfully created.' );
    }

}
