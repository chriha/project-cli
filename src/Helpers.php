<?php

namespace Chriha\ProjectCLI;

use Chriha\ProjectCLI\Libraries\Config\Project;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Helpers
{

    /**
     * @param null $name
     * @return Container|mixed
     * @throws BindingResolutionException
     */
    public static function app($name = null)
    {
        return $name ? Container::getInstance()->make($name) : Container::getInstance();
    }

    /**
     * @param string $path
     * @return string|null
     */
    public static function projectPath(string $path = '') : ?string
    {
        return ! PROJECT_PATHS_PROJECT ? null : PROJECT_PATHS_PROJECT . DS . ltrim($path, DS);
    }

    /**
     * @param string $path
     * @return string|null
     */
    public static function pluginsPath(string $path = '') : ?string
    {
        return self::home('plugins' . DS . $path);
    }

    /**
     * Return the home directory of ProjectCLI
     *
     * @param string $path
     * @return string|null
     */
    public static function home(string $path = '') : ?string
    {
        if ( ! PROJECT_PATHS_HOME) {
            return null;
        }

        return PROJECT_PATHS_HOME . DS . ltrim($path, DS);
    }

    /**
     * @param string $text
     * @throws BindingResolutionException
     */
    public static function line($text = '')
    {
        static::app('output')->writeln($text);
    }

    /**
     * @param $text
     * @throws BindingResolutionException
     */
    public static function danger($text)
    {
        static::app('output')->writeln('<fg=red>' . $text . '</>');
    }

    /**
     * Display a danger message and exit.
     *
     * @param string $text
     * @param null $exception
     * @return void
     * @throws BindingResolutionException
     */
    public static function abort($text, $exception = null)
    {
        static::danger($text);

        if ($exception) {
            static::logger()->debug($exception);
        }

        exit(1);
    }

    /**
     * Display the date in "humanized" time-ago form.
     *
     * @param string $date
     * @return string
     */
    public static function timeAgo($date)
    {
        return Carbon::parse($date)->diffForHumans();
    }

    /**
     * @param string $string
     * @return string
     */
    public static function mbStrReverse(string $string) : string
    {
        $r = '';

        for ($i = mb_strlen($string); $i >= 0; $i--) {
            $r .= mb_substr($string, $i, 1);
        }

        return $r;
    }

    /**
     * Check if the provided command exists on the host system
     *
     * @param string $command
     * @return bool
     */
    public static function commandExists(string $command) : bool
    {
        return ! ! `which {$command}`;
    }

    /**
     * Search in a file for the given string and return the first match
     *
     * @param string $search
     * @param string $file
     * @return string|null
     */
    public static function searchInFile(string $search, string $file) : ?string
    {
        $handle = @fopen($file, "r");

        if ( ! $handle) {
            return null;
        }

        while ( ! feof($handle)) {
            $buffer = fgets($handle);

            if (strpos($buffer, $search) !== false) {
                fclose($handle);

                return trim($buffer);
            }
        }

        fclose($handle);

        return null;
    }

    /**
     * Find a namespace within a PHP class
     *
     * @param string $file
     * @return string|null
     */
    public static function findNamespace(string $file) : ?string
    {
        $line = static::searchInFile('namespace', $file);

        if ( ! $line) {
            return null;
        }

        $position = strpos($line, 'namespace');

        return trim(rtrim(substr($line, $position + 9), ';'));
    }

    /**
     * @return string
     * @throws BindingResolutionException
     */
    public static function hostsFile() : string
    {
        if (PHP_OS === 'Linux') {
            return '/etc/hosts';
        }

        if (PHP_OS !== 'Darwin') {
            static::abort('Unsupported OS');
            exit(0);
        }

        return '/private/etc/hosts';
    }

    /**
     * @param string $dir
     * @return bool
     */
    public static function rmdir(string $dir) : bool
    {
        $process = new Process(['rm', '-rf', $dir]);

        return 0 === $process->run();
    }

    /**
     * @param string $dir
     */
    public static function recursiveRemoveDir(string $dir) : void
    {
        if (is_dir($dir)) {
            $files = scandir($dir);

            foreach ($files as $file) {
                if ($file == "." || $file == "..") {
                    continue;
                }

                static::recursiveRemoveDir("{$dir}/{$file}");
            }

            rmdir($dir);
        } elseif (file_exists($dir)) {
            unlink($dir);
        }
    }

    /**
     * @param string $src
     * @param string $dst
     */
    public static function recursiveCopy(string $src, string $dst) : void
    {
        if (file_exists($dst)) {
            static::recursiveRemoveDir($dst);
        }

        if (is_dir($src)) {
            mkdir($dst);
            $files = scandir($src);

            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                static::recursiveCopy("{$src}/{$file}", "{$dst}/{$file}");
            }
        } elseif (file_exists($src)) {
            copy($src, $dst);
        }
    }

    /**
     * @return LoggerInterface
     * @throws BindingResolutionException
     */
    public static function logger() : LoggerInterface
    {
        return static::app('logger');
    }

    public static function isProjectType(string $type) : bool
    {
        $config = new Project();

        if ( ! $config->hasConfig()) {
            return false;
        }

        return $config->get('type') === $type;
    }

}
