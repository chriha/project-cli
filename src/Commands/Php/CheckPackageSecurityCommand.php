<?php

namespace Chriha\ProjectCLI\Commands\Php;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Illuminate\Support\Str;
use SensioLabs\Security\SecurityChecker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CheckPackageSecurityCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'security:packages';

    /** @var string */
    protected $description = 'Check the security for your composer.lock file';

    /** @var bool */
    protected $requiresProject = true;


    public function configure() : void
    {
        $this->addArgument(
            'file',
            InputArgument::OPTIONAL,
            'Relative path to the composer.lock file',
            'src/composer.lock'
        );

        $this->addOption('fix', null, InputOption::VALUE_NONE, 'Fix possible vulnerabilities');
    }

    public function handle(SecurityChecker $checker) : void
    {
        $lockFile = Helpers::projectPath($this->argument('file'));

        if ( ! file_exists($lockFile)) {
            $this->abort("File '{$lockFile}' not found!");
        }

        $result = $checker->check($lockFile, 'json');
        $alerts = json_decode((string)$result, true);

        if (empty($alerts)) {
            $this->info('No vulnerabilities found!');
            exit;
        }

        $count = 0;

        foreach ($alerts as $package => $alert) {
            $vulnerabilityCount = count($alert['advisories']);
            $this->line(
                "<fg=red;options=bold>" . $package . "</> <fg=yellow>" . ($alert['version'] ?? null) . "</> ("
                . $vulnerabilityCount . " " . Str::plural('vulnerability', $vulnerabilityCount) . ")"
            );

            $count += count($alert['advisories']);

            foreach ($alert['advisories'] ?? [] as $vulnerability) {
                $this->line(
                    '<fg=blue>==></> <options=bold>' . $vulnerability['title'] . '</>'
                    . (! empty($vulnerability['cve']) ? ' (' . $vulnerability['cve'] . ')' : '')
                //. ' <fg=black>' . $vulnerability['link'] . '</>'
                );
            }
        }

        if ($count) {
            $string = "Total vulnerabilities found: {$count}";
            $length = Str::length(strip_tags($string)) + 12;

            $this->output->newLine();
            $this->comment(str_repeat('*', $length));
            $this->comment("*     {$string}     *");
            $this->comment(str_repeat('*', $length));
        }

        if ($this->option('fix') && ! empty($alerts)) {
            $this->warn('Fixing vulnerabilities ...');
            $this->call('composer', array_merge(['update'], array_keys($alerts)));
        }

        $this->output->newLine();
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE && Helpers::isProjectType('php');
    }

}
