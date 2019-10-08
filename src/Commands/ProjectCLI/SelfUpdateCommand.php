<?php

namespace Chriha\ProjectCLI\Commands\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use GuzzleHttp\Client;
use PHLAK\SemVer\Version;
use Symfony\Component\Console\Input\InputOption;

class SelfUpdateCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'self-update';


    public function configure() : void
    {
        $this->setDescription( 'Update ProjectCLI command' );
        $this->addOption( 'check', null, InputOption::VALUE_NONE, 'Check for updates' );
        $this->addOption( 'rollback', null, InputOption::VALUE_NONE, 'Rollback to previous version' );
    }

    public function handle() : void
    {
        $oldFileFull = $_SERVER['SCRIPT_NAME'];

        if ( $this->option( 'rollback' ) )
        {
            if ( ! file_exists( $oldFileFull . '.previous' ) )
            {
                $this->abort( 'No previous version available' );
            }

            $this->task( 'Rolling back to previous version', function() use ( $oldFileFull )
            {
                rename( $oldFileFull . '.previous', $oldFileFull );
                chmod( $oldFileFull, 0750 );
            } );

            exit;
        }

        $client  = new Client();
        $result  = $client->request( 'GET', 'https://api.github.com/repos/chriha/project-cli/releases/latest' );
        $release = json_decode( $result->getBody()->getContents(), true );
        $current = new Version( Helpers::app( 'app' )->getVersion() );
        $latest  = new Version( $release['tag_name'] );

        if ( ! $latest->gt( $current ) )
        {
            $this->info( 'You have the latest version: <options=bold>' . $current . '</>' );
            exit;
        }
        else
        {
            $this->warn( 'New version available: <options=bold>' . $latest . '</>' );
        }

        if ( $this->option( 'check' ) ) return;

        if ( ! $this->confirm( 'Would you like to update now?' ) )
        {
            $this->abort( 'Update aborted!' );
        }

        $fileUrl = $release['assets'][0]['browser_download_url'];

        $this->task( 'Backup current version', function() use ( $oldFileFull )
        {
            @unlink( $oldFileFull . '.previous' );
            copy( $oldFileFull, $oldFileFull . '.previous' );
        } );

        $this->task( 'Downloading new release', function() use ( $fileUrl, $oldFileFull )
        {
            $content = file_get_contents( $fileUrl );

            unlink( $oldFileFull );
            file_put_contents( $oldFileFull, $content );
            chmod( $oldFileFull, 0750 );
        } );

        $this->info( 'You are now using <options=bold>' . $latest . '</>' );
    }

}
