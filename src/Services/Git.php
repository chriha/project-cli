<?php

namespace Chriha\ProjectCLI\Services;

use Chriha\ProjectCLI\Helpers;
use PHLAK\SemVer\Version;
use Symfony\Component\Process\Process;

class Git
{

    /** @var string */
    protected $branch;

    /** @var string */
    protected $config;


    /**
     * Current branch
     *
     * @return string
     */
    public function branch() : string
    {
        if ( $this->branch ) return $this->branch;

        return $this->branch = trim( shell_exec( "git branch | grep \* | cut -d ' ' -f2" ) );
    }

    /**
     * Checks if the current branch is the one proved
     *
     * @param string $branch
     * @return bool
     */
    public function inBranch( string $branch ) : bool
    {
        return $this->branch() == $branch;
    }

    /**
     * Returns the latest tag on the current branch
     *
     * @return string|null
     */
    public function latestTag() : string
    {
        return trim( shell_exec(
            "git describe --tags $(git rev-list --tags --max-count=1) 2> /dev/null"
        ) );
    }

    /**
     * Returns the
     *
     * @param string|null $start
     * @param string $head
     * @return array
     */
    public function commitRange( ?string $start, string $head = 'HEAD' ) : array
    {
        $range = ! is_null( $start ) && ! empty( $start )
            ? "{$start}..{$head}" : '';

        $commits = explode( "\n", shell_exec(
            "git log {$range} --pretty=\"format:%h___%s___%ce___%b\""
        ) );

        if ( empty( $commits ) ) return [];

        foreach ( $commits as $key => $commit )
        {
            [ $hash, $subject, $committer, $body ] = explode( "___", $commit );

            $commits[$hash] = compact( 'hash', 'subject', 'committer', 'body' );

            unset( $commits[$key] );
        }

        return $commits;
    }

    public function tag( Version $version, bool $push = true ) : void
    {
        $process = new Process( [ 'git tag ' . $version->prefix() ] );

        if ( ! $push ) return;

        $process->run( function() use ( $version )
        {
            ( new Process( [ 'git push origin ' . $version->prefix() ] ) )->run();
        } );
    }

}
