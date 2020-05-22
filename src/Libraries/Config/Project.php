<?php

namespace Chriha\ProjectCLI\Libraries\Config;

use Chriha\ProjectCLI\Helpers;
use Illuminate\Support\Arr;
use PHLAK\SemVer\Version;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Project
{

    /** @var array */
    protected $config;

    /** @var string */
    protected $file = 'project.yml';


    /**
     * @return mixed
     */
    public function all()
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
     * @return Project
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
     * @return Project
     */
    public function unset(string $path) : self
    {
        if (empty($this->config)) {
            $this->loadConfig();
        }

        Arr::forget($this->config, $path);

        return $this->save();
    }

    public function hasConfig() : bool
    {
        $path = Helpers::projectPath($this->file);

        return $path && file_exists($path);
    }

    private function loadConfig()
    {
        if ( ! PROJECT_IS_INSIDE) {
            return;
        }

        $path = Helpers::projectPath($this->file);

        if ( ! $this->hasConfig()) {
            Helpers::abort("Unable to find project config '{$this->file}'");
        }

        try {
            $this->config = Yaml::parse(file_get_contents($path));
        } catch (ParseException $e) {
            Helpers::abort("Unable to parse project config '{$this->file}'");
        }
    }

    public function version(Version $version = null) : Version
    {
        if ($version) {
            $this->set('version', $version);
        }

        return ($version = $this->get('version'))
            ? new Version($version) : new Version();
    }

    public function __destruct()
    {
        $this->save();
    }

    public function save()
    {
        if (empty($this->config)) {
            return;
        }

        $config = $this->config;

        $config['version'] = $this->version()->prefix();
        $config['type']    = $this->config['type'] ?? null;

        ksort($config);

        $parsed = Yaml::dump($config, 6, 2);

        file_put_contents(Helpers::projectPath($this->file), $parsed);
    }

}
