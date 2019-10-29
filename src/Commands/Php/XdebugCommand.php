<?php

namespace Chriha\ProjectCLI\Commands\Php;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputOption;

class XdebugCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'php:xdebug';

    /** @var string */
    protected $description = 'Enable / Disable Xdebug';

    /** @var bool */
    protected $requiresProject = true;

    /** @var string|null */
    protected $version = null;

    /** @var string|null */
    protected $ini = null;

    /** @var string|null */
    protected $xdebugIni = null;


    protected function configure() : void
    {
        $this->addOption( 'enable', 'e', InputOption::VALUE_NONE, 'Enable debug' )
            ->addOption( 'disable', 'd', InputOption::VALUE_NONE, 'Disable debug' );
    }

    public function handle( Docker $docker ) : void
    {
        $this->ini = shell_exec( $docker->compose() . ' ' . $docker->runExec() . ' php -i' );

        preg_match( '/PHP Version =>\s(\d\.\d)/', $this->ini, $result );

        if ( ( ! $this->version = $result[1] ?? null ) )
        {
            $this->abort( 'Unable to get PHP version' );
        }

        $this->xdebugIni = "/etc/php/{$this->version}/mods-available/xdebug.ini";

        if ( $this->option( 'enable' ) )
        {
            $this->enable( $docker );
        }
        elseif ( $this->option( 'disable' ) )
        {
            $this->disable( $docker );
        }

        $isEnabled = $this->isXdebugEnabled();

        $this->output->writeln( "Status: "
            . ( $isEnabled ? "<info>enabled</info>" : "<red>disabled</red>" ) );

        if ( ! $isEnabled ) return;

        $settings = [
            'xdebug.idekey', 'xdebug.remote_host', 'xdebug.remote_log', 'xdebug.remote_port'
        ];

        $variables = explode( "\r\n", $this->ini );
        $variables = array_filter( $variables, function( $value ) use ( $settings )
        {
            if ( strpos( $value, 'xdebug.' ) !== 0 ) return false;

            $setting = explode( ' ', $value );

            return in_array( $setting[0], $settings );
        } );

        foreach ( $variables as $variable )
        {
            $this->output->writeln( $variable );
        }
    }

    protected function enable( Docker $docker ) : void
    {
        if ( $this->isXdebugEnabled() ) return;

        // BUG: 'host.docker.internal' only available on Mac
        shell_exec( $docker->compose() . ' ' . $docker->runExec()
            . " sed -i '' -e 's/xdebug.remote_host=.*/xdebug.remote_host=host\.docker\.internal/g' "
            . "'{$this->xdebugIni}'" );

        shell_exec( $docker->compose() . ' ' . $docker->runExec()
            . " ln -fs '{$this->xdebugIni}' '/etc/php/{$this->version}/cli/conf.d/20-xdebug.ini'" );
        shell_exec( $docker->compose() . ' ' . $docker->runExec()
            . " ln -fs '{$this->xdebugIni}' '/etc/php/{$this->version}/fpm/conf.d/20-xdebug.ini'" );

        shell_exec( $docker->compose() . ' ' . $docker->runExec()
            . " service php{$this->version}-fpm restart &> /dev/null" );

        $this->updateIni( $docker );
    }

    protected function disable( Docker $docker ) : void
    {
        if ( ! $this->isXdebugEnabled() ) return;

        shell_exec( $docker->compose() . ' ' . $docker->runExec()
            . " rm -f '/etc/php/{$this->version}/cli/conf.d/20-xdebug.ini'" );
        shell_exec( $docker->compose() . ' ' . $docker->runExec()
            . " rm -f '/etc/php/{$this->version}/fpm/conf.d/20-xdebug.ini'" );

        $docker->exec( 'web', [ 'service', "php{$this->version}-fpm", 'restart', '&>', '/dev/null' ] );

        $this->updateIni( $docker );
    }

    protected function isXdebugEnabled() : bool
    {
        return strpos( $this->ini, 'xdebug.remote_host' ) !== false;
    }

    protected function updateIni( Docker $docker ) : void
    {
        $this->ini = shell_exec( $docker->compose() . ' ' . $docker->runExec() . ' php -i' );
    }

    public static function isActive() : bool
    {
        return PROJECT_IS_INSIDE;
    }

}
