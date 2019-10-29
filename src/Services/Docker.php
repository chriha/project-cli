<?php

namespace Chriha\ProjectCLI\Services;

use Chriha\ProjectCLI\Helpers;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Process\Process;

class Docker
{

    /** @var string */
    protected $config;

    /** @var array */
    protected $services = [];


    public function config() : ?string
    {
        if ( ! PROJECT_IS_INSIDE ) return null;

        if ( $this->config ) return $this->config;

        $env   = ( $_ENV['ENV'] ?? null ) ? "{$_ENV['ENV']}." : 'local.';
        $files = [
            "docker-compose.{$env}yml", "docker-compose.{$env}yaml",
            "docker-compose.yml", "docker-compose.yaml"
        ];

        foreach ( $files as $file )
        {
            if ( ! file_exists( Helpers::projectPath( $file ) ) ) continue;

            return $this->config = Helpers::projectPath( $file );
        }

        throw new LogicException( 'Unable to find docker-compose file' );
    }

    /**
     * @param array $commands
     * @return Process
     */
    public function process( array $commands = [] ) : Process
    {
        return ( new Process(
            array_merge( [ 'docker-compose', '-f', $this->config() ], $commands )
        ) )->setTimeout( 3600 );
    }

    /**
     * @param string $service
     * @param array $commands
     * @return Process
     */
    public function exec( string $service, array $commands = [] ) : Process
    {
        if ( ! $this->isRunning( $service ) )
        {
            return $this->process( array_merge( [ 'run', '--rm', $service ], $commands ) );
        }

        return $this->process( array_merge( [ 'exec', $service ], $commands ) );
    }

    public function isRunning( string $service ) : bool
    {
        if ( isset( $this->services[$service] ) ) return $this->services[$service];

        $process = new Process( [
            "docker-compose",
            '-f',
            $this->config(),
            'exec',
            '-T',
            $service,
            'echo'
        ] );

        $process->run();

        return $this->services[$service] = $process->isSuccessful();
    }

    public function compose() : string
    {
        return "docker-compose -f {$this->config()}";
    }

    public function services() : array
    {
        $services = [];
        $process  = $this->process( [ 'ps', '--services' ] );

        $process->run( function( $type, $buffer ) use ( &$services )
        {
            $services = array_filter( preg_split( "/\n/", $buffer ), function( $value )
            {
                return ! empty( $value );
            } );
        } );

        return $services;
    }

    public function runExec( string $service = 'web' ) : string
    {
        return ! $this->isRunning( $service ) ? "run --rm {$service}" : "exec {$service}";
    }

    public function ports() : array
    {
        $ports = [];

        foreach ( $_ENV as $key => $value )
        {
            if ( strpos( $key, '_PORT' ) === false ) continue;

            $ports[] = $value;
        }

        return $ports;
    }

    public function hasOccupiedPorts() : array
    {
        $host    = '127.0.0.1';
        $blocked = [];

        foreach ( $this->ports() as $port )
        {
            $connection = @fsockopen( $host, $port );

            if ( ! is_resource( $connection ) ) continue;

            fclose( $connection );

            $blocked[] = $port;
        }

        return ! empty( $blocked ) ? $blocked : [];
    }

    public function ps( string $service ) : array
    {
        $info    = [];
        $process = $this->process( [ 'ps', $service ] );

        $process->run( function( $type, $buffer ) use ( &$info )
        {
            $output = substr( $buffer, strpos( $buffer, "\n" ) + 1 );
            $output = trim( substr( $output, strpos( $output, "\n" ) + 1 ) );

            if ( empty( $output ) ) return;

            $info = preg_split( "/\s{2,}/", $output );
        } );

        if ( empty( $info ) ) return [];

        preg_match_all(
            '/(\d{2,6})->(\d{2,6})/', $info[3] ?? '', $ports
        );

        $ports = array_map( function( $value )
        {
            return str_replace( '->', ':', $value );
        }, $ports[0] ?? [] );

        $container[] = [
            'name'    => $info[0],
            'running' => $info[2] == 'Up' ? '<fg=green>online</>' : '<fg=red>offline</>',
            'ports'   => implode( ', ', $ports ),
        ];

        return $container;
    }

    public function stats( string $service ) : array
    {
        $process = new Process( [
            'docker', 'stats', $service, '--format',
            '{{.CPUPerc}}__{{.MemPerc}}__{{.MemUsage}}', '--no-stream'
        ] );

        $process->run( function( $type, $buffer ) use ( &$info )
        {
            $info = explode( '__', $buffer );
        } );

        return [
            'cpu'    => trim( $info[0] ),
            'memory' => trim( $info[1] ),
            'limit'  => trim( $info[2] ),
        ];
    }

}
