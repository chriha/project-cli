<?php

namespace Chriha\ProjectCLI\Libraries\Logging;

use Closure;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

class Tail
{

    /** @var string */
    protected $storageFile;

    /** @var array */
    protected $fileList = [];

    /** @var array */
    protected $fileMemory = [];


    /**
     * Tail constructor.
     * @param string $storageFile
     * @throws Exception
     */
    public function __construct( string $storageFile )
    {
        $dirname = dirname( $storageFile );

        if ( ! is_dir( $dirname ) )
        {
            throw new Exception( "{$dirname} is not a directory." );
        }
        elseif ( ! is_writable( $dirname ) )
        {
            throw new Exception( "Can't write to {$dirname}." );
        }

        $this->storageFile = $storageFile;

        if ( file_exists( $this->storageFile ) )
        {
            $this->fileMemory = json_decode(
                file_get_contents( $this->storageFile ), 1 );
        }
    }

    /**
     * @param Closure $callback
     * @param bool $fullLine
     * @throws Exception
     */
    public function listen( Closure $callback, bool $fullLine = false )
    {
        if ( ! is_callable( $callback ) )
        {
            throw new Exception( "Invalid callback function." );
        }

        while ( 1 )
        {
            $startTime = microtime( true );

            foreach ( $this->fileList as $filename )
            {
                clearstatcache();

                $fileSize = filesize( $filename );

                // if the filename is unknown, we save the present offset
                if ( $this->getFileSavedSize( $filename ) === -1 )
                {
                    $this->setFileOffset( $filename, $fileSize );
                    $this->setFileSavedSize( $filename, $fileSize );

                    continue;
                }

                // check that the file hasn't scrunk, if it has, reset the pointer
                if ( $this->getFileSavedSize( $filename ) > $fileSize )
                {
                    $this->setFileSavedSize( $filename, 0 );
                    $this->setFileOffset( $filename, 0 );
                }

                // if nothing has happened with the filesize, we do nothing ...
                if ( $this->getFileSavedSize( $filename ) == $fileSize )
                {
                    continue;
                }

                $this->setFileSavedSize( $filename, $fileSize );

                // at this point we know the file size has expanded.
                // read in the latest changes.
                $offset  = $this->getFileOffset( $filename );
                $content = $this->getExcerpt( $filename, $offset, $fileSize - $offset );

                if ( $content === false || empty( $content ) ) continue;

                if ( $fullLine )
                {
                    $x = strpos( $content, "\n" );

                    if ( $x === false ) continue;

                    $content = substr( $content, 0, $x + 1 );

                    // Update the offset of the filename
                    $this->setFileOffset( $filename, $offset + strlen( $content ) );

                    $content = trim( $content );
                }
                else
                {
                    // Update the offset of the filename
                    $this->setFileOffset( $filename, $fileSize );
                }

                if ( $callback( $filename, $content, $fileSize ) === false ) break;
            }

            // sleep for at least half a second..
            $t = 500000 - ( microtime( true ) - $startTime );

            if ( $t > 0 )
            {
                usleep( $t );
            }
        }
    }

    /**
     * @param Closure $callback
     */
    public function listenForLines( Closure $callback ) : void
    {
        $this->listen( $callback, true );
    }

    /**
     * @param string $filename
     * @param $offset
     * @param $length
     * @return bool|string
     */
    private function getExcerpt( string $filename, $offset, $length )
    {
        if ( $length <= 0 ) return false;

        if ( ( $handle = @fopen( $filename, 'r' ) ) === false ) return false;

        fseek( $handle, $offset );
        $content = fread( $handle, $length );
        fclose( $handle );

        return $content;
    }

    /**
     * @param string $filename
     * @param $offset
     */
    private function setFileOffset( string $filename, $offset ) : void
    {
        $this->fileMemory[$filename]['offset'] = $offset;

        file_put_contents( $this->storageFile, json_encode( $this->fileMemory ) );
    }

    /**
     * @param string $filename
     * @return int
     */
    private function getFileOffset( string $filename ) : int
    {
        if ( ! isset( $this->fileMemory[$filename]['offset'] ) )
        {
            $this->fileMemory[$filename]['offset'] = -1;
        }

        return $this->fileMemory[$filename]['offset'];
    }

    /**
     * @param string $filename
     * @param int $size
     */
    private function setFileSavedSize( string $filename, int $size ) : void
    {
        $this->fileMemory[$filename]['size'] = $size;

        file_put_contents( $this->storageFile, json_encode( $this->fileMemory ) );
    }

    /**
     * @param string $filename
     * @return int
     */
    private function getFileSavedSize( string $filename ) : int
    {
        if ( ! isset( $this->fileMemory[$filename]['size'] ) )
        {
            $this->fileMemory[$filename]['size'] = -1;
        }

        return $this->fileMemory[$filename]['size'];
    }

    /**
     * @param string $filename
     * @return Tail
     * @throws Exception
     */
    public function addFile( string $filename ) : self
    {
        if ( ! file_exists( $filename ) || ! is_file( $filename ) )
        {
            throw new Exception( "Could not find file: $filename." );
        }

        $filename = realpath( $filename );

        if ( ! in_array( $filename, $this->fileList ) )
        {
            $this->fileList[] = $filename;
        }

        return $this;
    }

    public static function logFilesInDirectory() : array
    {
        $dir      = new RecursiveDirectoryIterator( getcwd() );
        $iterator = new RecursiveIteratorIterator( $dir );
        $regex    = new RegexIterator( $iterator, '/^.+(\.log)$/i', RecursiveRegexIterator::GET_MATCH );
        $files    = [];
        $exclude  = [ '/vendor', '/src/vendor', '/temp' ];

        array_walk( $exclude, function( &$value, $key )
        {
            $value = getcwd() . $value;
        } );

        foreach ( $regex as $file => $result )
        {
            $ignore = array_filter( $exclude, function( $value ) use ( $file )
            {
                return strpos( $file, $value ) === 0;
            } );

            if ( ! empty( $ignore ) ) continue;

            $files[] = $file;
        }

        return $files;
    }

}
