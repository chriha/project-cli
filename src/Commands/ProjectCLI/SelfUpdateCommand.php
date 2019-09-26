<?php

namespace Chriha\ProjectCLI\Commands\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class SelfUpdateCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'self-update--';


    public function configure() : void
    {
        $this->setDescription( 'Update the project command' );
        $this->addOption( 'check', null, InputOption::VALUE_NONE, 'Check for updates' );
    }

    public function handle() : void
    {
        if ( $this->option( 'check' ) && ( $version = $this->isUpdateAvailable() ) )
        {
            $this->warn( "New version available: <info>{$version}</info>" );

            if ( ! $this->confirm( "Would you like to update?", false ) ) return;

            $this->call( 'self-update' );

            return;
        }

        $this->spinner( 'updating', new Process( [ 'test' ] ) );
    }

    private function isUpdateAvailable()
    {
        // TODO: fetch latest version
        $version   = '1.0.0-rc.1';
        $available = version_compare( config( 'app.version' ), $version ) < 0
            ? $version : false;

        // TODO: if new update available, set flag in config

        return $available;
    }

}
