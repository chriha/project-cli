<?php

namespace Chriha\ProjectCLI;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Container\Container;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

class Helpers
{

    /**
     * Display a danger message and exit.
     *
     * @param string $text
     * @return void
     */
    public static function abort( $text )
    {
        static::danger( $text );

        exit( 1 );
    }

    public static function app( $name = null )
    {
        return $name ? Container::getInstance()->make( $name ) : Container::getInstance();
    }

    public static function projectPath( string $path = '' ) : ?string
    {
        $root = static::app( 'project.path' );

        return ! $root ? null : $root . DS . ltrim( $path, DS );
    }

    public static function rootPath( string $path = '' ) : ?string
    {
        $trim = 'app';//config( 'app.production' ) ? 'project/app' : 'app';
        $file = rtrim( dirname( __FILE__ ), $trim );
        $path = $file . ltrim( $path, "/" );

        return str_replace( '///', '/', str_replace( 'phar://', '', $path ) );
    }

    public static function home( string $path = '' ) : string
    {
        return self::app( 'paths.home' ) . DS . ltrim( $path, DS );
    }

    /**
     * @todo: extract to own Config class
     */
    public static function configFile( string $path = '' ) : ?string
    {
        $root = static::projectPath( 'project.yaml' );

        return ! $root ? null : $root . DS . ltrim( $path, '/' );
    }

    public static function ask( $question, $default = null )
    {
        $style = new SymfonyStyle( static::app( 'input' ), static::app( 'output' ) );

        return $style->ask( $question, $default );
    }

    public static function comment( $text )
    {
        static::app( 'output' )->writeln( '<comment>' . $text . '</comment>' );
    }

    public static function confirm( $question, $default = true )
    {
        $style = new SymfonyStyle( static::app( 'input' ), static::app( 'output' ) );

        return $style->confirm( $question, $default );
    }

    public static function danger( $text )
    {
        static::app( 'output' )->writeln( '<fg=red>' . $text . '</>' );
    }

    /**
     * Get a random exclamation.
     *
     * @return string
     */
    public static function exclaim()
    {
        return Arr::random( [
            'Amazing',
            'Awesome',
            'Beautiful',
            'Boom',
            'Cool',
            'Done',
            'Got it',
            'Great',
            'Magic',
            'Nice',
            'Sweet',
            'Wonderful',
            'Yes',
        ] );
    }

    public static function info( $text )
    {
        static::app( 'output' )->writeln( '<info>' . $text . '</info>' );
    }

    public static function line( $text = '' )
    {
        static::app( 'output' )->writeln( $text );
    }

    public static function secret( $question )
    {
        $style = new SymfonyStyle( static::app( 'input' ), static::app( 'output' ) );

        return $style->askHidden( $question );
    }

    public static function step( $text )
    {
        static::line( '<fg=blue>==></> ' . $text );
    }

    public static function table( array $headers, array $rows, $style = 'borderless' )
    {
        if ( empty( $rows ) )
        {
            return;
        }

        $table = new Table( static::app( 'output' ) );

        $table->setHeaders( $headers )->setRows( $rows )->setStyle( $style )->render();
    }

    /**
     * Display the date in "humanized" time-ago form.
     *
     * @param string $date
     * @return string
     */
    public static function time_ago( $date )
    {
        return Carbon::parse( $date )->diffForHumans();
    }

    public static function write( $text )
    {
        static::app( 'output' )->write( $text );
    }

    public static function mbStrReverse( string $string ) : string
    {
        $r = '';

        for ( $i = mb_strlen( $string ); $i >= 0; $i-- )
        {
            $r .= mb_substr( $string, $i, 1 );
        }

        return $r;
    }

}
