<?php

namespace Chriha\ProjectCLI\Services;

class Git
{

    /** @var string */
    protected $branch;

    /** @var string */
    protected $config;


    public function __construct()
    {
        //
    }

    public function branch() : string
    {
        if ( $this->branch ) return $this->branch;

        return $this->branch = trim( shell_exec( "git branch | grep \* | cut -d ' ' -f2" ) );
    }

    public function inBranch( string $branch ) : bool
    {
        return $this->branch() == $branch;
    }

}
