<?php

namespace Chriha\ProjectCLI\Commands\Make;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Symfony\Component\Console\Input\InputArgument;

class PluginCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'make:plugin';


    public function configure() : void
    {
        $this->setDescription( 'Create a plugin' )
            ->addArgument( 'cmd', InputArgument::REQUIRED, 'The name of the command' );
    }

    public function handle() : void
    {
        $stub = __DIR__ . DS . '..' . DS . '..' . DS . 'Stubs' . DS . 'plugin.stub';

        $cmd   = $this->argument( 'cmd' );
        $last  = strrpos( $cmd, '/' );
        $path  = substr( $cmd, 0, $last );
        $class = substr( $cmd, $last );
        $dir   = "plugins" . DS . $path;
        $file  = Helpers::home( $dir . DS . $class . '.php' );

        if ( file_exists( $file ) )
        {
            $this->abort( 'Plugin with this name already exists!' );
        }

        @mkdir( Helpers::home( $dir ), 0755, true );

        $content = file_get_contents( $stub );
        $content = str_replace( 'DummyClass', ltrim( $class, '/' ), $content );

        if ( strpos( $cmd, DS ) !== false )
        {
            $namespace = implode( '\\', array_slice( explode( "/", $cmd ), 0, -1 ) );
            $content   = str_replace( '\\DummyNamespace', "\\{$namespace}", $content );
        }
        else
        {
            $content   = str_replace( '\\DummyNamespace', '', $content );
        }

        file_put_contents( $file, $content );

        $this->info( 'Plugin successfully created.' );
    }

}
