<?php

namespace Chriha\ProjectCLI\Libraries\Ssh;

use Chriha\ProjectCLI\Helpers;
use Closure;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Process\Process;

class Processor
{

    use Macroable;

    /** @var OutputStyle */
    private $output;

    /** @var Connection */
    private $connection;

    /** @var bool */
    protected $break;

    public function __construct( OutputStyle $output, Connection $connection, bool $break = true )
    {
        $this->output     = $output;
        $this->connection = $connection;
        $this->break      = $break;
    }

    public function run( string $title, $command, Closure $callback = null ) : int
    {
        /** @var Process $process */
        $process = $this->getProcess( $command );

        return $this->execute( $title, $process, $callback );
    }

    protected function execute( string $title, Process $process, Closure $callback = null ) : int
    {
        $callback = $callback ?: function() use ( $title )
        {
        };

        $interval = 70000;
        $frames   = $this->getFrames();
        $key      = reset( $frames );

        $process->start( function( $type, $output ) use ( $callback )
        {
            $callback( $this->connection->getEnvironment(), $output, $type );
        } );

        while ( $process->isRunning() )
        {
            if ( empty( ( $incremental = $process->getIncrementalOutput() ) ) )
            {
                $this->updateOutput( "<options=bold><comment>{$key}</comment>: {$title}</>" );
            }
            else
            {
                $this->addOutput( $incremental, true );
            }

            $key = ( $key = next( $frames ) ) === false ? reset( $frames ) : $key;
            usleep( $interval );
        }

        $output = empty( $process->getOutput() )
            ? $process->getErrorOutput() : $process->getOutput();
        $lines  = explode( "\n", $output );

        $this->output->write( "\x0D" );
        $this->output->write( "\x1B[2k" );

        $this->output->writeln( "<options=bold><comment>[{$this->connection->getEnvironment()}]</comment>: {$title}</>" );

        if ( empty( $lines ) ) return $process->getExitCode();

        $this->addOutput( $lines, $process->isSuccessful() );

        if ( ! $process->isSuccessful() && $this->break ) exit( $process->getExitCode() );

        return $process->getExitCode();
    }

    public function copy( string $title, string $source, string $target, Closure $callback = null ) : int
    {
        $command = $this->connection->getRsyncCommand()
            . " --rsync-path=\"sudo rsync\" -lr {$source} {$this->connection->getHost()}:{$target}";

        /** @var Process $process */
        $process = new Process( [ $command ] );

        return $this->execute( $title, $process, $callback );
    }

    public function getProcess( $script ) : Process
    {
        $env = $this->getEnvironmentVariables( $this->connection->getEnvironment() );

        foreach ( $env as $k => $v )
        {
            if ( $v === false ) continue;

            $env[$k] = 'export ' . $k . '="' . $v . '"' . PHP_EOL;
        }

        $delimiter = 'EOF-PROJECT-CLI';

        if ( $this->connection->isLocal() )
        {
            $process = new Process( $script );
        }
        else
        {
            $process = new Process(
                $this->connection->getSshCommand() . " 'bash -se' << \\$delimiter" . PHP_EOL
                . implode( PHP_EOL, $env ) . PHP_EOL
                . 'set -e' . PHP_EOL
                . ( is_array( $script ) ? implode( " && ", $script ) : $script ) . PHP_EOL
                . $delimiter
            );
        }

        return $process->setTimeout( null );
    }

    protected function getEnvironmentVariables( $host )
    {
        return [
            'PROJECT_CLI_HOST' => $host
        ];
    }

    /**
     * Gather the cumulative exit code for the processes.
     *
     * @param array $processes
     * @return int
     */
    protected function gatherExitCodes( array $processes )
    {
        $code = 0;

        foreach ( $processes as $process )
        {
            $code = $code + $process->getExitCode();
        }

        return $code;
    }

    protected function getFrames()
    {
        $ball   = "●";
        $frames = [];
        $length = max( strlen( $this->connection->getEnvironment() ), 5 ) - 1;
        $left   = 1;

        for ( $i = 0; $i < $length; $i++ )
        {
            $frames[] = "[" . str_repeat( " ", $left ) . "{$ball}" . str_repeat( " ", $length - ( $left++ ) ) . "]";
        }

        foreach ( $frames as $frame )
        {
            $frames[] = "[" . Helpers::mbStrReverse( substr( $frame, 1, -1 ) ) . "]";
        }

        return $frames;
    }

    protected function updateOutput( string $content ) : void
    {
        // Determines if we can use escape sequences
        if ( $this->output->isDecorated() )
        {
            // Move the cursor to the beginning of the line
            $this->output->write( "\x0D" );
            // Erase the line
            $this->output->write( "\x1B[2K" );
        }
        else
        {
            $this->output->writeln( '' ); // Make sure we first close the previous line
        }

        $this->output->write( $content );
    }

    protected function addOutput( $content, bool $success ) : void
    {
        $lines = ! is_array( $content ) ? [ $content ] : $content;

        $this->output->write( "\x0D" );
        $this->output->write( "\x1B[2K" );

        foreach ( $lines as $line )
        {
            if ( strlen( trim( $line ) ) === 0 ) continue;

            if ( $success )
            {
                $this->output->writeln( '<comment>[' . $this->connection->getEnvironment() . ']</comment>: '
                    . trim( $line ) );
            }
            else
            {
                $this->output->writeln( '<comment>[' . $this->connection->getEnvironment() . ']</comment>: <fg=red>'
                    . trim( $line ) . '</>' );
            }
        }
    }

}
