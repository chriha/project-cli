<?php

namespace Chriha\ProjectCLI\Libraries\Ssh;

use Chriha\ProjectCLI\Helpers;
use Exception;

class Config
{

    /** @var array */
    private $hosts = [];

    /** @var string */
    private $path;

    /** @var array */
    private $available = [
        'AddKeysToAgent',
        'ForwardAgent',
        'HostName',
        'IdentityFile',
        'LocalForward',
        'Port',
        'Protocol',
        'ProxyCommand',
        'StrictHostKeyChecking',
        'UseKeychain',
        'User',
    ];

    public function __construct(string $path = null)
    {
        $this->path = $path ?? $_SERVER['HOME'] . '/.ssh/config';
    }

    public function parse() : self
    {
        if ( ! file_exists($this->path)) {
            Helpers::abort('Missing SSH config');
        }

        $this->hosts = [];
        $contents    = file_get_contents($this->path);
        $lines       = explode("\n", $contents);
        $host        = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || substr($line, 0, 1) === '#') {
                continue;
            }

            [$_, $key, $value] = $this->parseLine($line);

            if ($key === 'Host' && $value !== '*') {
                $this->hosts[$host = $value] = [];
            } elseif ($key !== 'Host' && ! is_null($host)) {
                $this->hosts[$host][$key] = $value;
            }
        }

        ksort($this->hosts);

        foreach ($this->hosts as $host => $settings) {
            foreach ($this->available as $key) {
                if (array_key_exists($key, $settings)) {
                    continue;
                }

                $this->hosts[$host][$key] = null;
            }

            ksort($this->hosts[$host]);
        }

        return $this;
    }

    public function get(string $host = null, ?string $key = null)
    {
        if (empty($this->hosts)) {
            $this->parse();
        }

        if (is_null($host)) {
            return $this->hosts;
        }

        if (is_null($key)) {
            return $this->hosts[$host] ?? null;
        }

        return $this->hosts[$host][$key] ?? null;
    }

    public function set(string $host, string $key, $value, bool $save = true) : self
    {
        if (empty($this->hosts)) {
            $this->parse();
        }

        $this->hosts[$host][$key] = empty($value) ? null : $value;

        if ($save) {
            $this->save();
        }

        return $this;
    }

    public function setHost(string $host, array $settings, bool $save = true) : self
    {
        $this->hosts[$host] = $settings;

        if ($save) {
            $this->save();
        }

        return $this;
    }

    public function remove(string $host) : self
    {
        if ( ! isset($this->hosts[$host])) {
            return $this;
        }

        unset($this->hosts[$host]);

        $this->save();

        return $this;
    }

    /**
     * @param string $line
     * @return array
     * @throws Exception
     */
    protected function parseLine(string $line) : array
    {
        if ( ! preg_match('/(\w+)(?:\s*=\s*|\s+)(.+)/', trim($line), $match)) {
            echo $line . PHP_EOL;
            throw new Exception('Invalid SSH config');
        }

        return $match;
    }

    public function save(string $path = null) : self
    {
        if (empty($this->hosts)) {
            return $this;
        }

        @unlink("{$this->path}.bak.2");

        if (file_exists("{$this->path}.bak.1")) {
            @rename("{$this->path}.bak.1", "{$this->path}.bak.2");
        }

        copy($this->path, "{$this->path}.bak.1");

        $content = "";

        foreach ($this->hosts as $host => $values) {
            $content .= "\nHost {$host}\n";

            foreach ($values as $key => $value) {
                if (is_null($value)) {
                    continue;
                }

                $content .= "    {$key} {$value}\n";
            }
        }

        file_put_contents($path ?? $this->path, ltrim($content, "\n"));

        return $this;
    }

}
