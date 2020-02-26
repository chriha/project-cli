<?php

namespace Chriha\ProjectCLI\Services\Plugins;

use Chriha\ProjectCLI\Helpers;
use Chriha\ProjectCLI\Services\Git;
use Symfony\Component\Console\Style\OutputStyle;

class Plugin
{

    /** @var array */
    protected $info = [];

    public function __construct(array $info)
    {
        $this->info = $info;
    }

    public function install() : bool
    {
        //
    }

    public function uninstall() : bool
    {
        if ( ! $this->info['name']) {
            return false;
        }

        $path = Helpers::home('plugins' . DS . $this->info['name']);

        Helpers::recursiveRemoveDir($path);

        return ! is_dir($path);
    }

    public function isInstalled() : bool
    {
        return is_dir(Helpers::home('plugins' . DS . $this->info['name']));
    }

    public function asListItem() : void
    {
        /** @var OutputStyle $output */
        $output = Helpers::app('output');

        $output->writeln(
            '<fg=blue>::</> <options=bold>' . $this->info['name'] . '</>'
            . ' [' . $this->info['version'] . ']'
            . ($this->isInstalled() ? ' <fg=green>(installed)</>' : '')
        );
    }

    public function asItem() : void
    {
        /** @var OutputStyle $output */
        $output = Helpers::app('output');

        $output->writeln(
            '<fg=blue>::</> <options=bold>' . $this->info['name'] . '</>'
            . ' [' . $this->info['version'] . ']'
            . ($this->isInstalled() ? ' <fg=green>(installed)</>' : '')
        );

        if ( ! empty($this->info['title'])) {
            $output->writeln($this->info['title']);
        }

        if ( ! empty($this->info['short_description'])) {
            $output->writeln('<options=bold>short_description:</>');
            $output->writeln($this->info['description'] ?? '-');
        }

        $output->writeln('');
    }

    public function tag() : string
    {
        return Git::tagByPath(Helpers::pluginsPath($this->name)) ?: 'dev';
    }

    public function __toString() : string
    {
        return $this->info['title'] ?? 'No title available';
    }

    public function __get(string $name)
    {
        if ( ! isset($this->info[$name])) {
            return null;
        }

        return $this->info[$name];
    }

}
