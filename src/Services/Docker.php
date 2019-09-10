<?php

namespace Chriha\ProjectCLI\Services;

use Chriha\ProjectCLI\Helpers;
use Symfony\Component\Process\Process;

class Docker
{

    /** @var string */
    protected $config;

    /** @var array */
    protected $services = [];


    public function config() : ?string
    {
        if ( ! Helpers::app( 'project.inside' ) ) return null;

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

        if ( ! Helpers::app( 'project.inside' ) ) return null;

        return null;
    }

    /**
     * @param array $commands
     * @return Process
     */
    public function process( array $commands = [] ) : Process
    {
        return new Process( array_merge( [ 'docker-compose', '-f', $this->config() ], $commands ) );
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

}
