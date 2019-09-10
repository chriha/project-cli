<?php

namespace Chriha\ProjectCLI\Commands;

use Chriha\ProjectCLI\Helpers;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

trait ProvidesOutput
{

    /** @var OutputInterface */
    public $output;

    public function spinner( string $title, Process $process, \Closure $output = null ) : Process
    {
        $interval = 50000;
        $frames   = [ "⠋", "⠙", "⠹", "⠸", "⠼", "⠴", "⠦", "⠧", "⠇", "⠏" ];
        $key      = reset( $frames );

        $process->start( $output );

        while ( $process->isRunning() )
        {
            if ( $this->output->isDecorated() )
            {
                // Determines if we can use escape sequences
                // Move the cursor to the beginning of the line
                $this->output->write( "\x0D" );
                // Erase the line
                $this->output->write( "\x1B[2K" );
            }
            else
            {
                $this->output->writeln( '' ); // Make sure we first close the previous line
            }

            $this->output->write( "{$key} $title" );

            $key = ( $key = next( $frames ) ) === false ? reset( $frames ) : $key;
            usleep( $interval );
        }

        $this->output->write( "\x0D" );
        $this->output->write( "\x1B[2K" );
        $this->output->writeln(
            ( $process->isSuccessful() ? '<info>✔</info>' : '<error>failed:</error>' ) . " {$title}"
        );

        if ( ! $process->isSuccessful() )
        {
            $output = ! empty( $process->getErrorOutput() )
                ? $process->getErrorOutput() : $process->getOutput();

            Helpers::abort( $output );
        }

        return $process;
    }

    /**
     * Format input into a textual table.
     *
     * @param array $headers
     * @param array $rows
     * @param string $style
     * @return void
     */
    public function table( array $headers, array $rows, $style = 'borderless' )
    {
        Helpers::table( $headers, $rows, $style );
    }

    /**
     * @param string|null $message
     */
    public function exit( ?string $message ) : void
    {
        Helpers::abort( $message );
    }

    /**
     * @param string|null $message
     * @param null $default
     * @return mixed
     */
    public function ask( string $message, $default = null )
    {
        return Helpers::ask( $message, $default );
    }

    /**
     * @param string|null $message
     * @return void
     */
    public function info( string $message ) : void
    {
        Helpers::info( $message );
    }

    /**
     * Write a string as standard output.
     *
     * @param string $string
     * @param string $style
     * @return void
     */
    public function line( $string, $style = null )
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln( $styled );
    }

    /**
     * Write a string as warning output.
     *
     * @param string $string
     * @return void
     */
    public function warn( $string )
    {
        if ( ! $this->output->getFormatter()->hasStyle( 'warning' ) )
        {
            $style = new OutputFormatterStyle( 'yellow' );

            $this->output->getFormatter()->setStyle( 'warning', $style );
        }

        $this->line( $string, 'warning' );
    }

    /**
     * Display the given output line.
     *
     * @param int $type
     * @param string $host
     * @param string $line
     * @return void
     */
    protected function displayOutput( $type, $host, $line )
    {
        $this->output->write( "\x0D" );
        $this->output->write( "\x1B[2K" );
        $lines = explode( "\n", $line );

        foreach ( $lines as $line )
        {
            if ( strlen( trim( $line ) ) === 0 ) continue;

            if ( $type == Process::OUT )
            {
                $this->output->write( '<comment>[' . $host . ']</comment>: '
                    . trim( $line ) . PHP_EOL );
            }
            else
            {
                $this->output->write( '<comment>[' . $host . ']</comment>: <fg=red>'
                    . trim( $line ) . '</>' . PHP_EOL );
            }
        }
    }

    /*
     * Performs the given task, outputs and returns the result.
     *
     * @param  string $title
     * @param  callable|null $task
     * @return bool With the result of the task.
     */
    protected function task( string $title, \Closure $task = null, $loadingText = 'loading ...' )
    {
        $this->output->write( "$title: <comment>{$loadingText}</comment>" );

        if ( $task === null )
        {
            $result = true;
        }
        else
        {
            try
            {
                $result = $task() === false ? false : true;
            }
            catch ( \Exception $taskException )
            {
                $result = false;
            }
        }

        if ( $this->output->isDecorated() )
        {
            // Determines if we can use escape sequences
            // Move the cursor to the beginning of the line
            $this->output->write( "\x0D" );
            // Erase the line
            $this->output->write( "\x1B[2K" );
        }
        else
        {
            $this->output->writeln( '' ); // Make sure we first close the previous line
        }

        $this->output->writeln(
            "$title: " . ( $result ? '<info>✔</info>' : '<error>failed</error>' )
        );

        if ( isset( $taskException ) )
        {
            throw $taskException;
        }

        return $result;
    }

}
