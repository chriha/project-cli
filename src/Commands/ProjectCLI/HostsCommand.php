<?php

namespace Chriha\ProjectCLI\Commands\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Symfony\Component\Console\Input\InputOption;

class HostsCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'hosts';

    /** @var string */
    protected $description = 'Manage hosts file';

    /** @var array */
    protected $hosts = [];


    public function configure() : void
    {
        $this->addOption( 'disable', 'd', InputOption::VALUE_REQUIRED, 'Disable host' );
        $this->addOption( 'enable', 'e', InputOption::VALUE_REQUIRED, 'Enable host' );
        $this->addOption( 'remove', 'r', InputOption::VALUE_REQUIRED, 'Remove host' );
        $this->addOption( 'set', 's', InputOption::VALUE_REQUIRED, 'Set a host, comma separated (eg 127.0.0.1,www.example.com)' );
    }

    public function handle() : void
    {
        $this->getHosts();
        $save = true;

        if ( ( $host = $this->option( 'disable' ) ) && $this->checkHost( $host ) )
        {
            $this->hosts[$host]['enabled'] = false;
        }
        elseif ( ( $host = $this->option( 'enable' ) ) && $this->checkHost( $host ) )
        {
            $this->hosts[$host]['enabled'] = true;
        }
        elseif ( ( $host = $this->option( 'remove' ) ) && $this->checkHost( $host ) )
        {
            unset( $this->hosts[$host] );
        }
        elseif ( $set = $this->option( 'set' ) )
        {
            [ $ip, $host ] = explode( ',', $set );

            $this->hosts[$host] = [
                'enabled' => true,
                'ip'      => $ip,
                'host'    => $host,
            ];
        }
        else
        {
            $save = false;
        }

        if ( $save ) $this->save();

        $this->list();
    }

    protected function checkHost( string $host ) : bool
    {
        if ( isset( $this->hosts[$host] ) ) return true;

        $this->abort( 'Unknown host' );
    }

    protected function getHosts() : array
    {
        $file   = Helpers::hostsFile();
        $isSafe = false;
        $hosts  = [];
        $handle = fopen( $file, "r" );

        while ( ( $line = fgets( $handle ) ) !== false )
        {
            $line = trim( $line );

            if ( empty( $line ) ) continue;

            $pos = strpos( $line, 'DO NOT CHANGE THE LINES ABOVE' );

            if ( ! $isSafe && $pos === false ) continue;

            if ( ! $isSafe && ( $isSafe = true ) ) continue;

            [ $ip, $host ] = explode( ' ', $line );

            if ( empty( $host ) ) continue;

            $hosts[$host] = [
                'enabled' => substr( $ip, 0, 1 ) !== '#',
                'ip'      => ltrim( $ip, '#' ),
                'host'    => $host
            ];
        }

        fclose( $handle );

        return $this->hosts = $hosts;
    }

    protected function list() : void
    {
        if ( empty( $this->hosts ) ) return;

        $list = [];

        foreach ( $this->hosts as $key => $host )
        {
            if ( ! $host['enabled'] )
            {
                $list[$key] = [
                    '<fg=black>  -</>',
                    sprintf( '<fg=black>%s</>', $host['ip'] ),
                    sprintf( '<fg=black>%s</>', $host['host'] )
                ];
            }
            else
            {
                $list[$key] = [ '  ✓', $host['ip'], $host['host'] ];
            }
        }

        $this->table( [ 'Status', 'IP', 'Host' ], $list );
    }

    protected function save() : void
    {
        if ( posix_getuid() !== 0 )
        {
            $this->abort( 'Superuser privileges required to change the hosts file!' );
        }

        $file   = Helpers::hostsFile();
        $handle = fopen( $file, "r" );
        $lines  = [];

        while ( ( $line = fgets( $handle ) ) !== false )
        {
            $line = trim( $line );

            if ( empty( $line ) ) continue;

            $pos = strpos( $line, 'DO NOT CHANGE THE LINES ABOVE' );

            $lines[] = $line;

            if ( $pos !== false ) break;
        }

        fclose( $handle );

        if ( empty( $lines ) )
        {
            $this->abort( 'The hosts file seems to be broken' );
        }

        foreach ( $this->hosts as $host )
        {
            $lines[] = ( $host['enabled'] ? '' : '#' )
                . sprintf( '%s %s', $host['ip'], $host['host'] );
        }

        copy( $file, $file . '.bak.1' );
        file_put_contents( $file, implode( "\n", $lines ) );
    }

}
