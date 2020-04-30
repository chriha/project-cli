<?php

namespace Chriha\ProjectCLI\Commands\Make;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Libraries\Config\Project;
use Chriha\ProjectCLI\Services\Git;
use PHLAK\SemVer\Version;
use Symfony\Component\Process\Process;

class ReleaseCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'make:release';

    /** @var string */
    protected $description = 'Start assistant to create a new release for the project';

    /** @var bool */
    protected $requiresProject = true;

    /** @var Version */
    protected $release;

    /** @var array */
    protected $commits = [];


    public function handle(Project $config, Git $git) : void
    {
        if ( ! $git->isClean()) {
            $this->abort('Working directory is not clean.');
        }

        $branch = $git->branch();

        if ( ! $git->inBranch('master')
            && ! $this->confirm(
                'You are not in master. Are you sure you want to proceed?'
            )) {
            $this->abort('Aborted!');
        } elseif (empty($branch)) {
            $this->abort('Not in a branch. Did you initialize Git?');
        }

        if ( ! $config->get('version')
            && $this->confirm('You haven\'t created a version yet. Create now?')) {
            $this->release = $config->version();
        } elseif ( ! $config->get('version')) {
            $this->abort('Abort!');
        } else {
            $this->release = $config->version();
        }

        $gitTag = $git->latestTag();

        if (empty($gitTag)) {
            $this->warn("No previous tags found.");

            $latest = null;
        } else {
            $latest = new Version($gitTag);
        }

        if ( ! $this->confirm(
            'Is ' . $this->release->prefix() . ' the release version?'
        )) {
            $choices = [
                'Bump patch (backwards compatible bug fixes)',
                'Bump minor (new functionality in a backwards compatible manner)',
                'Bump major (incompatible API changes)',
                'Abort'
            ];

            $answer = array_search(
                $this->choice('What would you like to do?', $choices, 0),
                $choices
            );

            switch ($answer) {
                case 0:
                    $this->release->incrementPatch();
                    break;
                case 1:
                    $this->release->incrementMinor();
                    break;
                case 2:
                    $this->release->incrementMajor();
                    break;
                default:
                    $this->abort('Abort!');
                    break;
            }

            $config->version($this->release);
            $config->save();
            $git->commit('bump version to ' . $this->release->prefix(), true);

            $this->info('New release version is: ' . $this->release->prefix());
        }

        if ( ! is_null($latest) && $latest->gt($this->release)) {
            $this->abort('Latest tag is higher than release version!');
        }

        //if ( ! is_null($latest) && $this->confirm(
        //        'Would you like to see the commits in this release and add notes / a changelog?',
        //        false
        //    )) {
        //    $this->commits = $git->commitRange($latest->prefix());
        //
        //    foreach ($this->commits as $hash => $commit) {
        //        $this->output->write(
        //            "<comment>" . $commit['hash'] . "</comment> " . $commit['subject']
        //        );
        //        $this->commits[$hash]['note'] = $this->ask('Anything to add?');
        //    }
        //
        //    $this->comment('Release notes / changelog:');
        //
        //    foreach ($this->commits as $hash => $commit) {
        //        if (empty($commit['note'])) {
        //            continue;
        //        }
        //    }
        //}

        $this->task(
            'Creating tag',
            function () use ($git)
            {
                $git->tag($this->release, false);
            }
        );

        if ($this->confirm(
            'Would you like to push this tag? <comment>' . $this->release->prefix(
            ) . '</comment>'
        )) {
            $this->task(
                'Pushing tag',
                function () use ($git)
                {
                    (new Process(['git push origin ' . $this->release->prefix()]))->run();
                }
            );
        }

        if ( ! $this->getApplication()->has('deploy')) {
            return;
        }

        if ( ! $this->confirm('Would you like to deploy this release now?')) {
            return;
        }

        $environments = $config->get('environments') ?? ['stage', 'production', 'test'];
        $environment  = $this->choice('Choose an environment', $environments, 0);

        if ( ! $this->confirm(
            'Deploying ' . $this->release->prefix() . ' to ' . $environment . '?'
        )) {
            return;
        }

        $this->call(
            'deploy',
            [
                'tag' => $this->release->prefix(),
                'environment' => $environment
            ]
        );
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE;
    }

}
