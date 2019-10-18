<?php

namespace Chriha\ProjectCLI\Traits;

use Chriha\ProjectCLI\Helpers;
use Closure;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

trait ProvidesOutput
{

    /** @var SymfonyStyle */
    public $output;

    public function spinner( string $title, Process $process, Closure $output = null ) : Process
    {
        $interval = 50000;
        $frames   = [ "⠋", "⠙", "⠹", "⠸", "⠼", "⠴", "⠦", "⠧", "⠇", "⠏" ];
        $key      = reset( $frames );

        $process->start( $output );

        while ( $process->isRunning() )
        {
            if ( $this->output->isDecorated() )
            {
                // Determines if we can use escape sequences
                // Move the cursor to the beginning of the line
                $this->output->write( "\x0D" );
                // Erase the line
                $this->output->write( "\x1B[2K" );
            }
            else
            {
                $this->output->writeln( '' ); // Make sure we first close the previous line
            }

            $this->output->write( "{$key} $title" );

            $key = ( $key = next( $frames ) ) === false ? reset( $frames ) : $key;
            usleep( $interval );
        }

        $this->output->write( "\x0D" );
        $this->output->write( "\x1B[2K" );
        $this->output->writeln(
            ( $process->isSuccessful() ? '<info>✔</info>' : '<error>failed:</error>' ) . " {$title}"
        );

        if ( ! $process->isSuccessful() )
        {
            $output = ! empty( $process->getErrorOutput() )
                ? $process->getErrorOutput() : $process->getOutput();

            Helpers::abort( $output );
        }

        return $process;
    }

    /**
     * Format input to textual table.
     *
     * @param array $headers
     * @param array $rows
     * @param string $tableStyle
     * @param array $columnStyles
     * @return void
     */
    public function table( array $headers, array $rows, $tableStyle = 'default', array $columnStyles = [] )
    {
        $table = new Table( $this->output );

        if ( $rows instanceof Arrayable )
        {
            $rows = $rows->toArray();
        }

        $table->setHeaders( (array)$headers )->setRows( $rows )->setStyle( $tableStyle );

        foreach ( $columnStyles as $columnIndex => $columnStyle )
        {
            $table->setColumnStyle( $columnIndex, $columnStyle );
        }

        $table->render();
    }

    /**
     * @param string|null $message
     */
    public function abort( string $message ) : void
    {
        $this->error( $message );
        exit( 1 );
    }

    /**
     * @param string $question
     * @param null $default
     * @param null $validator
     * @return mixed
     */
    public function ask( string $question, $default = null, $validator = null )
    {
        return $this->output->ask( $question, $default, $validator );
    }

    /**
     * @param string $string
     * @return void
     */
    public function info( string $string ) : void
    {
        $this->line( $string, 'info' );
    }

    /**
     * Write a string as standard output.
     *
     * @param string $string
     * @param string $style
     * @return void
     */
    public function line( $string, $style = null )
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln( $styled );
    }

    public function example( string $command ) : void
    {
        $this->line( "  $ {$command}" . PHP_EOL );
    }

    /**
     * Write a string as warning output.
     *
     * @param string $string
     * @return void
     */
    public function warn( $string )
    {
        if ( ! $this->output->getFormatter()->hasStyle( 'warning' ) )
        {
            $style = new OutputFormatterStyle( 'yellow' );

            $this->output->getFormatter()->setStyle( 'warning', $style );
        }

        $this->line( $string, 'warning' );
    }

    /**
     * Confirm a question with the user.
     *
     * @param string $question
     * @param bool $default
     * @return bool
     */
    public function confirm( string $question, bool $default = true )
    {
        return $this->output->confirm( $question, $default );
    }

    /**
     * Write a string in an alert box.
     *
     * @param string $string
     * @return void
     */
    public function alert( $string )
    {
        $length = Str::length( strip_tags( $string ) ) + 12;

        $this->comment( str_repeat( '*', $length ) );
        $this->comment( '*     ' . $string . '     *' );
        $this->comment( str_repeat( '*', $length ) );

        $this->output->newLine();
    }

    /**
     * Write a string as comment output.
     *
     * @param string $string
     * @return void
     */
    public function comment( $string )
    {
        $this->line( $string, 'comment' );
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param string $question
     * @param array $choices
     * @param string|null $default
     * @return mixed
     */
    public function anticipate( $question, array $choices, $default = null )
    {
        return $this->askWithCompletion( $question, $choices, $default );
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param string $question
     * @param array $choices
     * @param string|null $default
     * @return mixed
     */
    public function askWithCompletion( $question, array $choices, $default = null )
    {
        $question = new Question( $question, $default );

        $question->setAutocompleterValues( $choices );

        return $this->output->askQuestion( $question );
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * @param string $question
     * @param array $choices
     * @param string|null $default
     * @param bool|null $multiple
     * @param mixed|null $attempts
     * @param string|null $errorMessage
     * @return string
     */
    public function choice( $question, array $choices, $default = null, $multiple = null, $attempts = null, ?string $errorMessage = null )
    {
        $question = new ChoiceQuestion( $question, $choices, $default );

        $question->setMaxAttempts( $attempts )
            ->setMultiselect( $multiple )
            ->setErrorMessage( $errorMessage );

        return $this->output->askQuestion( $question );
    }

    /**
     * Write a string as question output.
     *
     * @param string $string
     * @return void
     */
    public function question( $string )
    {
        $this->line( $string, 'question' );
    }

    /**
     * Write a string as error output.
     *
     * @param string $string
     * @return void
     */
    public function error( $string )
    {
        $this->line( $string, 'red' );
    }

    /**
     * Write line as step
     *
     * @param $text
     */
    public function step( $text )
    {
        $this->line( '<fg=blue>==></> ' . $text );
    }

    /**
     * Hide the user input
     *
     * @param $question
     * @return mixed
     */
    public function secret( $question )
    {
        return $this->output->askHidden( $question );
    }

    /**
     * Display the given output line.
     *
     * @param int $type
     * @param string $host
     * @param string $line
     * @return void
     */
    protected function displayOutput( $type, $host, $line )
    {
        $this->output->write( "\x0D" );
        $this->output->write( "\x1B[2K" );
        $lines = explode( "\n", $line );

        foreach ( $lines as $line )
        {
            if ( strlen( trim( $line ) ) === 0 ) continue;

            if ( $type == Process::OUT )
            {
                $this->output->write( '<comment>[' . $host . ']</comment>: '
                    . trim( $line ) . PHP_EOL );
            }
            else
            {
                $this->output->write( '<comment>[' . $host . ']</comment>: <fg=red>'
                    . trim( $line ) . '</>' . PHP_EOL );
            }
        }
    }

    /*
     * Performs the given task, outputs and returns the result.
     *
     * @param  string $title
     * @param  callable|null $task
     * @return bool With the result of the task.
     */
    protected function task( string $title, Closure $task = null, $loadingText = 'loading ...' )
    {
        $this->output->write( "$title: <comment>{$loadingText}</comment>" );

        if ( $task === null )
        {
            $result = true;
        }
        else
        {
            try
            {
                $result = $task() === false ? false : true;
            }
            catch ( Exception $taskException )
            {
                $result = false;
            }
        }

        if ( $this->output->isDecorated() )
        {
            // Determines if we can use escape sequences
            // Move the cursor to the beginning of the line
            $this->output->write( "\x0D" );
            // Erase the line
            $this->output->write( "\x1B[2K" );
        }
        else
        {
            $this->output->writeln( '' ); // Make sure we first close the previous line
        }

        $this->output->writeln(
            "$title: " . ( $result ? '<info>✔</info>' : '<error>failed</error>' )
        );

        if ( isset( $taskException ) )
        {
            throw $taskException;
        }

        return $result;
    }

}
