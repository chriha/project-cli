<?php

namespace Chriha\ProjectCLI\Console\Output;

use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProjectStyle extends SymfonyStyle
{

    /** @var SymfonyQuestionHelper */
    private $questionHelper;

    /** @var InputInterface */
    protected $input;

    /** @var BufferedOutput */
    private $bufferedOutput;


    public function __construct( InputInterface $input, OutputInterface $output )
    {
        $this->input          = $input;
        $this->bufferedOutput = new BufferedOutput(
            $output->getVerbosity(), false, clone $output->getFormatter()
        );

        parent::__construct( $input, $output );
    }

    /**
     * @param Question $question
     * @return mixed
     */
    public function askQuestion( Question $question )
    {
        //if ( $this->input->isInteractive() )
        //{
        //    $this->autoPrependBlock();
        //}

        if ( ! $this->questionHelper )
        {
            $this->questionHelper = new SymfonyQuestionHelper();
        }

        $answer = $this->questionHelper->ask( $this->input, $this, $question );

        //if ( $this->input->isInteractive() )
        //{
        //    $this->newLine();
        //    $this->bufferedOutput->write("\n");
        //}

        return $answer;
    }

    private function autoPrependBlock() : void
    {
        $chars = substr( str_replace( PHP_EOL, "\n", $this->bufferedOutput->fetch() ), -2 );

        if ( ! isset( $chars[0] ) )
        {
            $this->newLine(); //empty history, so we should start with a new line.

            return;
        }
        //Prepend new line for each non LF chars (This means no blank line was output before)
        $this->newLine( 2 - substr_count( $chars, "\n" ) );
    }

}
