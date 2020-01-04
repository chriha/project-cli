<?php

namespace Chriha\ProjectCLI\Contracts;

interface Plugin
{

    /**
     * Configure the command by adding a description, arguments and options
     *
     * @return void
     */
    public function configure() : void;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() : void;

}
