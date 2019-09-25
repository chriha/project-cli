<?php

namespace Chriha\ProjectCLI\Commands\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Libraries\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class VersionCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'version';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->setDescription( 'Show or set application version' );
        $this->addOption( 'major', null, InputOption::VALUE_NONE, 'Increment major version' );
        $this->addOption( 'minor', null, InputOption::VALUE_NONE, 'Increment minor version' );
        $this->addOption( 'patch', null, InputOption::VALUE_NONE, 'Increment patch version' );
        $this->addOption( 'pre', null, InputOption::VALUE_REQUIRED, 'Set pre release version' );
        $this->addOption( 'build', null, InputOption::VALUE_REQUIRED, 'Set build version' );
        $this->addArgument( 'version', InputArgument::OPTIONAL, 'Specify a new version' );
    }

    public function handle( Config $config ) : void
    {
        $version = $config->version();

        if ( $this->argument( 'version' ) )
        {
            $version->setVersion( $this->argument( 'version' ) );
        }
        elseif ( $this->option( 'major' ) )
        {
            $version->incrementMajor();
        }
        elseif ( $this->option( 'minor' ) )
        {
            $version->incrementMinor();
        }
        elseif ( $this->option( 'patch' ) )
        {
            $version->incrementPatch();
        }
        elseif ( $this->option( 'pre' ) )
        {
            $version->setPreRelease( $this->option( 'pre' ) );
        }
        elseif ( $this->option( 'build' ) )
        {
            $version->setBuild( $this->option( 'build' ) );
        }
        else
        {
            $this->output->writeln( '<info>Project Version:</info> ' . $version->prefix() );
        }

        if ( ! $config->version()->eq( $version ) )
        {
            $config->version( $version );
        }
    }

}
