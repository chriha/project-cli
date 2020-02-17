<?php

namespace Chriha\ProjectCLI\Commands\Logs;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Libraries\Logging\Tail;
use Symfony\Component\Console\Input\InputOption;

class ClearCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'logs:clear';

    /** @var string */
    protected $description = 'Clear log files';


    protected function configure() : void
    {
        $this->addOption(
            'file',
            'f',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'The files you want to clear'
        );
    }

    public function handle() : void
    {
        $files = $this->option('file');

        if ( ! $files || empty($files)) {
            if (!$this->confirm('You are about to clear all log files. Continue?')) {
                $this->abort('Aborted');
            }

            $files = [];

            $this->task(
                'Searching for log files',
                function () use (&$files)
                {
                    $files = Tail::logFilesInDirectory();
                }
            );
        }

        if (empty($files)) {
            $this->abort('No files specified or found.');
        }

        foreach ($files as $file) {
            $this->task(
                "Clearing <comment>{$file}</comment>",
                function () use ($file)
                {
                    $handle = @fopen($file, "r+");

                    if ($handle === false) {
                        return false;
                    }

                    ftruncate($handle, 0);
                    fclose($handle);

                    return true;
                }
            );
        }
    }

}
