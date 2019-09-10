<?php

namespace Chriha\ProjectCLI\Commands\Php;

use Chriha\ProjectCLI\Commands\Command;
use Chriha\ProjectCLI\Services\Docker;
use Symfony\Component\Console\Input\InputOption;

class XdebugCommand extends Command
{

    /** @var string */
    protected static $defaultName = 'php:xdebug';

    /** @var bool */
    protected $requiresProject = true;

    /** @var string|null */
    protected $version = null;

    /** @var string|null */
    protected $hostIp = null;

    /** @var string|null */
    protected $ini = null;

    /** @var string|null */
    protected $xdebugIni = null;


    protected function configure() : void
    {
        $this->setDescription( 'Enable / Disable Xdebug' )
            ->addOption( 'enable', null, InputOption::VALUE_OPTIONAL, 'Enable debug' )
            ->addOption( 'disable', null, InputOption::VALUE_OPTIONAL, 'Disable debug' );
    }

    public function handle( Docker $docker ) : void
    {
        $this->ini = shell_exec( $docker->compose() . ' ' . $docker->runExec() . ' php -i' );

        preg_match( '/PHP Version =>\s(\d\.\d)/', $this->ini, $result );

        if ( ( ! $this->version = $result[1] ?? null ) )
        {
            $this->exit( 'Unable to get PHP version' );
        }

        $this->hostIp    = gethostbyname( gethostname() );
        $this->xdebugIni = "/etc/php/{$this->version}/mods-available/xdebug.ini";

        if ( $this->option( 'enable' ) )
        {
            $this->enable( $docker );
        }
        elseif ( $this->option( 'disable' ) )
        {
            $this->disable( $docker );
        }

        $this->output->writeln( "Status: "
            . ( $this->isXdebugEnabled() ? "<info>enabled</info>" : "<red>disabled</red>" ) );
    }

    protected function enable( Docker $docker ) : void
    {
        if ( $this->isXdebugEnabled() ) return;

        shell_exec( $docker->compose() . ' ' . $docker->runExec()
            . " sed -i '' -e 's/xdebug.remote_host=.*/xdebug.remote_host={$this->hostIp}/g' '{$this->xdebugIni}'" );

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
            . " rm -f '{$this->xdebugIni}' '/etc/php/{$this->version}/cli/conf.d/20-xdebug.ini'" );
        shell_exec( $docker->compose() . ' ' . $docker->runExec()
            . " rm -f '{$this->xdebugIni}' '/etc/php/{$this->version}/fpm/conf.d/20-xdebug.ini'" );

        shell_exec( $docker->compose() . ' ' . $docker->runExec()
            . " service php{$this->version}-fpm restart &> /dev/null" );

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

}
