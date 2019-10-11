<?php

namespace Chriha\ProjectCLI\Libraries\Config;

use Chriha\ProjectCLI\Helpers;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Application
{

    /** @var array */
    protected $config;

    /** @var string */
    protected $file = '.config.yml';


    public function __construct()
    {
        define( 'DS', DIRECTORY_SEPARATOR );

        $home = $_SERVER['HOME'] . DS . '.project';

        if ( ! is_dir( $home ) ) mkdir( $home, 700, true );

        define( 'PROJECT_PATHS_HOME', $home );

        $this->loadConfig();

        if ( ! PROJECT_IS_INSIDE ) return;

        if ( ! in_array( Helpers::projectPath(), $this->config['projects'] ?? [] ) )
        {
            $this->config['projects'][] = Helpers::projectPath();
        }
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function get( string $path )
    {
        if ( empty( $this->config ) )
        {
            $this->loadConfig();
        }

        return Arr::get( $this->config, $path );
    }

    /**
     * @param string $path
     * @param $value
     * @return Application
     */
    public function set( string $path, $value ) : self
    {
        $this->config = Arr::set( $this->config, $path, $value );

        return $this;
    }

    private function loadConfig()
    {
        $path = Helpers::home( $this->file );

        if ( ! $path || ! is_file( $path ) )
        {
            $this->config = require_once __DIR__ . '/../../Config/default.php';
        }
        else
        {
            try
            {
                $this->config = Yaml::parse( file_get_contents( $path ) );
            }
            catch ( ParseException $e )
            {
                Helpers::abort( "Unable to parse project config '{$this->file}'" );
            }
        }
    }

    public function __destruct()
    {
        $this->save();
    }

    public function save()
    {
        if ( empty( $this->config ) ) return;

        $config = $this->config;

        ksort( $config );

        $parsed = Yaml::dump( $config );

        file_put_contents( Helpers::home( $this->file ), $parsed );
    }

}
