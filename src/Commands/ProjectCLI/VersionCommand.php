<?php

namespace Chriha\ProjectCLI\Commands\ProjectCLI;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Helpers;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class VersionCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'app:version';

    /** @var bool */
    protected $requiresProject = true;

    /** @var string */
    protected $file;

    /** @var array */
    protected $version = [];


    public function configure() : void
    {
        $this->setDescription( 'Show or set application version' );
        $this->addOption( 'major', null, InputOption::VALUE_OPTIONAL, 'Major version' );
        $this->addOption( 'minor', null, InputOption::VALUE_OPTIONAL, 'Minor version' );
        $this->addOption( 'patch', null, InputOption::VALUE_OPTIONAL, 'Patch version' );
        $this->addOption( 'build', null, InputOption::VALUE_OPTIONAL, 'Build version' );
        $this->addArgument( 'version', InputArgument::OPTIONAL, 'Specify a new version' );
    }

    public function handle() : void
    {
        $this->getVersion();
        dump( $this->version() );
    }

    protected function getVersion() : array
    {
        $this->file = Helpers::projectPath( DS . "src" . DS . "config" . DS . "version.php" );

        if ( ! ( $exists = file_exists( $this->file ) )
            && Helpers::confirm( 'Would you like to create it now?' ) )
        {
            file_put_contents( $this->file, $this->getConfigTemplate() );
        }
        elseif ( ! $exists )
        {
            $this->exit( 'No configuration file available' );
        }

        return $this->version = include( $this->file );
    }

    protected function getConfigTemplate( int $major = 0, int $minor = 0, int $patch = 0, int $build = 0 ) : string
    {
        return "<?php

return [
    'major' => {$major},
    'minor' => {$minor},
    'patch' => {$patch},
    'build' => {$build},
];
\n";
    }

    protected function version() : string
    {
        $string = 'v' . implode( '.', $this->version );

        return substr_replace( $string, '-', strrpos( $string, '.' ), strlen( '.' ) );
    }

    protected function updateVersion( array $version ) : bool
    {
        $content = $this->getConfigTemplate( $version['major'], $version['minor'], $version['patch'], $version['build'] );

        return !! file_put_contents( $this->file, $content );
    }

}
