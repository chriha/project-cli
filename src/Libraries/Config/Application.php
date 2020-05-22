<?php

namespace Chriha\ProjectCLI\Libraries\Config;

use Chriha\ProjectCLI\Helpers;
use Exception;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Application
{

    /** @var array */
    protected $config;

    /** @var string */
    protected $file = 'config.yml';

    /** @var bool */
    protected $errored = false;


    public function __construct()
    {
        $home = $_SERVER['HOME'] . DS . '.project';

        if ( ! is_dir($home)) {
            mkdir($home, 0750, true);
        }

        if ( ! defined('PROJECT_PATHS_HOME')) {
            define('PROJECT_PATHS_HOME', $home);
        }

        $this->loadConfig();

        if ( ! PROJECT_IS_INSIDE) {
            return;
        }

        if ( ! in_array(Helpers::projectPath(), $this->config['projects'] ?? [])) {
            $this->config['projects'][] = Helpers::projectPath();
        }
    }

    /**
     * @return mixed
     */
    public function all() : ?array
    {
        if (empty($this->config)) {
            $this->loadConfig();
        }

        return $this->config;
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function get(string $path)
    {
        if (empty($this->config)) {
            $this->loadConfig();
        }

        return Arr::get($this->config, $path);
    }

    /**
     * @param string $path
     * @param $value
     * @return Application
     */
    public function set(string $path, $value) : self
    {
        if (empty($this->config)) {
            $this->loadConfig();
        }

        Arr::set($this->config, $path, $value);

        return $this->save();
    }

    /**
     * @param string $path
     * @return Application
     */
    public function unset(string $path) : self
    {
        if (empty($this->config)) {
            $this->loadConfig();
        }

        Arr::forget($this->config, $path);

        return $this->save();
    }

    private function loadConfig()
    {
        $path    = Helpers::home($this->file);
        $default = require __DIR__ . '/../../Config/default.php';

        try {
            if ( ! $path || ! is_file($path)) {
                $this->config = $default;
            } else {
                try {
                    $this->config = Yaml::parse(file_get_contents($path));
                } catch (ParseException $e) {
                    Helpers::abort("Unable to parse project config '{$this->file}'");
                }

                $this->config = array_merge($default, $this->config);
            }
        } catch (Exception $e) {
            $this->errored = true;
        }
    }

    public function save() : self
    {
        if (empty($this->config) || $this->errored) {
            return $this;
        }

        ksort($this->config);

        $parsed = Yaml::dump($this->config, 6, 2);

        file_put_contents(Helpers::home($this->file), $parsed);

        return $this;
    }

}
