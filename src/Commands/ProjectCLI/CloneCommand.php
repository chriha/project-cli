<?php

namespace Chriha\ProjectCLI\Commands\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;

class CloneCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'clone';

    /** @var string */
    protected $description = 'Clone a Git repository and setup the project';


    public function configure() : void
    {
        $this->addArgument( 'repository', InputArgument::REQUIRED, 'Repository you want to clone' )
            ->addArgument( 'directory', InputArgument::OPTIONAL, 'Project directory' );
    }

    /**
     * @return mixed
     */
    public function handle() : void
    {
        if ( PROJECT_IS_INSIDE )
        {
            $this->abort( "You are currently in a project" );
        }

        $repository = $this->argument( 'repository' );
        $directory  = $this->argument( 'directory' ) ?? pathinfo( $repository, PATHINFO_FILENAME );

        $this->spinner( 'Cloning repository', new Process( [ 'git', 'clone', '-q', $repository, $directory ] ) );

        if ( ! $this->getApplication()->has( 'install' ) ) return;

        if ( ! $this->ask( 'Would you like to install the project?', 'no' ) ) return;

        $this->call( 'install' );
    }

}
