<?php

namespace Chriha\ProjectCLI\Libraries\Ssh;

class Connection
{

    /** @var string */
    private $env;

    /** @var string */
    private $host;

    public function __construct(string $env, string $host)
    {
        $this->env  = $env;
        $this->host = $host;
    }

    public function isLocal() : bool
    {
        return in_array($this->host, ['local', 'localhost', '127.0.0.1']);
    }

    public function getEnvironment() : string
    {
        return $this->env;
    }

    public function getHost() : string
    {
        return $this->host;
    }

    public function getSshCommand() : string
    {
        return "ssh {$this->host}";
    }

    public function getRsyncCommand() : string
    {
        $proxy   = (new Config)->get($this->getHost(), 'ProxyCommand');
        $command = "rsync ";

        // add proxy command for jump hosts
        if ($proxy) {
            $command .= "-e \"ssh -o ProxyCommand='{$proxy}'\"";
        }

        return $command;
    }

}
