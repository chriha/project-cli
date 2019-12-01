<?php

namespace Chriha\ProjectCLI\Commands\Logs;

use Chriha\ProjectCLI\Commands\Command;
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
            'The file you want to clear'
        );
    }

    public function handle() : void
    {
        if (empty($this->option('file'))) {
            $this->abort('No file specified.');
        }

        foreach ($this->option('file') as $file) {
            $this->task(
                "Emptying <comment>{$file}</comment>",
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
