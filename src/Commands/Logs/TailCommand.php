<?php

namespace Chriha\ProjectCLI\Commands\Logs;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Libraries\Logging\Tail;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputOption;

class TailCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'logs:tail';

    /** @var string */
    protected $description = 'Tail log files of the project';

    /** @var array */
    protected $reds = [ 'alert', 'emergency', 'error', 'fatal', 'critical', 'failed' ];

    /** @var array */
    protected $yellows = [ 'warning', 'debug' ];

    /** @var array */
    protected $greens = [ 'info', 'processed' ];

    /** @var bool */
    private $informedAboutLogSize = false;


    protected function configure() : void
    {
        $this->addOption( 'file', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The file to tail' );
        //->addOption( 'ignore-type', 'i', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The file to tail' );
    }

    public function handle() : void
    {
        $styleRed  = new OutputFormatterStyle( 'red' );
        $styleBlue = new OutputFormatterStyle( 'blue' );
        $detached  = false;//$this->option( 'detached' );

        $this->output->getFormatter()->setStyle( 'error', $styleRed );
        $this->output->getFormatter()->setStyle( 'default', $styleBlue );

        $files = $this->option( 'file' );

        if ( ! $files || empty( $files ) )
        {
            $files = [];

            $this->task( 'Searching for log files', function() use ( &$files )
            {
                $files = Tail::logFilesInDirectory();
            } );
        }

        if ( empty( $files ) )
        {
            $this->abort( 'No files specified!' );
        }

        $tail = new Tail( Helpers::home( 'tailed-logs.json' ) );

        foreach ( $files as $key => $file )
        {
            if ( ! file_exists( $file ) )
            {
                $this->warn( "'{$file}' does not exist and will be ignored" );

                unset( $files[$key] );

                continue;
            }

            $this->output->writeln( "Listening to <comment>{$file}</comment> ..." );

            if ( $detached ) continue;

            $this->logFileInfo( $file );
            $tail->addFile( $file );
        }

        if ( empty( $files ) )
        {
            $this->abort( 'No file to tail' );
        }

        //  TODO: tbd
        if ( $detached )
        {
            var_dump( implode( ' --file=', $this->option( 'file' ) ) );
            exit;
            shell_exec( sprintf( '%s > /dev/null 2>&1 &', "logs:tail {$files}" ) );
        }

        $tail->listen( function( $file, $chunk, $fileSize )
        {
            foreach ( explode( "\n", $chunk ) as $line )
            {
                $this->logFileInfo( $file, $fileSize );

                if ( substr( $line, 0, 1 ) !== '[' ) continue;
                //if ( substr( $line, 0, 1 ) !== '[' && substr( $line, 0, 1 ) === '#' ) continue;

                $line = trim( $line );

                if ( empty( $line ) ) continue;

                preg_match( "/^(\[[0-9-\s:]+\])[?[0-9]*]? ([a-z]*)[\s\.]?([a-zA-Z]+): (.+)/", $line, $parts );

                // TODO: if not valid, just print it with current timestamp
                if ( empty( $parts ) || count( $parts ) < 5 ) continue;

                if ( in_array( strtolower( $parts[3] ), $this->reds ) )
                {
                    $parts[3] = "<error>{$parts[3]}</error>";
                }
                elseif ( in_array( strtolower( $parts[3] ), $this->yellows ) )
                {
                    $parts[3] = "<comment>{$parts[3]}</comment>";
                }
                elseif ( in_array( strtolower( $parts[3] ), $this->greens ) )
                {
                    $parts[3] = "<info>{$parts[3]}</info>";
                }

                $this->output->writeln( "<default>{$parts[1]}</default> {$parts[2]} {$parts[3]} {$parts[4]}" );
            }
        } );
    }

    /**
     * @param string $file
     * @param int|null $size
     */
    private function logFileInfo( string $file, int $size = null ) : void
    {
        if ( $this->informedAboutLogSize ) return;

        $size = $size ?? filesize( $file );

        if ( $size < 2000000 ) return;

        $this->info( "Your log file '{$file}' is getting big (" . round( ( $size / 1000000 ), 3 ) . " MB). Consider clearing it for better performance." );

        $this->informedAboutLogSize = true;
        sleep( 5 );
    }

}
