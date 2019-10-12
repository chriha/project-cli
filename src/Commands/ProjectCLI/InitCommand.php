<?php

namespace Chriha\ProjectCLI\Commands\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class InitCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'init';

    /** @var array */
    protected $types = [
        'default' => 'https://github.com/chriha/project-cli-env-laravel.git',
        'laravel' => 'https://github.com/chriha/project-cli-env-laravel.git'
    ];


    public function configure() : void
    {
        $this->setDescription( 'Setup a new project' );
        $this->addOption( 'type', 't', InputOption::VALUE_OPTIONAL, 'Type of the project', 'default' );
        $this->addOption( 'directory', 'd', InputOption::VALUE_OPTIONAL, 'Project directory' );
        $this->addOption( 'setup', null, InputOption::VALUE_NONE, 'Setup the project by its type' );
    }

    /**
     * Execute the console command.
     *
     * @param Docker $docker
     * @return mixed
     */
    public function handle( Docker $docker ) : void
    {
        if ( PROJECT_IS_INSIDE )
        {
            $this->abort( "You are currently in a project" );
        }

        if ( $this->input->hasOption( 'type' )
            && ! in_array( $this->option( 'type' ), array_keys( $this->types ) ) )
        {
            $this->abort( "Unknown type: {$this->option('type')}" );
        }

        $repository = $this->types[$this->option( 'type' ) ?? 'default'];
        $directory  = $this->option( 'directory' ) ?? pathinfo( $repository, PATHINFO_FILENAME );
        $clone      = new Process( [ 'git', 'clone', '-q', $repository, $directory ] );

        $this->spinner( 'Setting up project', $clone );

        if ( ! $this->option( 'type' ) || ! $this->input->hasOption( 'setup' ) ) return;

        chdir( $directory );
        Helpers::recursiveRemoveDir( '.git' );
        copy( '.env.example', '.env' );
        touch( 'src' . DS . '.env' );

        // TODO: check for open ports

        if ( $this->option( 'type' ) == 'laravel' )
        {
            if ( ! empty( $blocked = $docker->hasOccupiedPorts() ) )
            {
                $this->abort( "Ports are already occupied: " . implode( ', ', $blocked ) );
            }

            $this->setupLaravel();
        }

        $this->info( "Project '{$directory}' successfully set up" );
    }

    private function setupLaravel() : void
    {
        $destination = '_setup';

        $this->spinner( 'Setting up Laravel', new Process( [
            'project', 'composer', 'create-project', 'laravel/laravel', $destination
        ], getcwd() ) );

        $this->call( 'down' );

        // move setup into temp
        rename( getcwd() . DS . "src" . DS . $destination, getcwd() . DS . "temp" . DS . "src" );
        // rm src directory
        Helpers::recursiveRemoveDir( getcwd() . DS . "src" );
        // mv temp/src into .
        rename( getcwd() . DS . "temp" . DS . "src", getcwd() . DS . "src" );
    }

}
