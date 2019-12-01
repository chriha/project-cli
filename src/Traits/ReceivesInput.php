<?php

namespace Chriha\ProjectCLI\Traits;

use Chriha\ProjectCLI\Console\Input\InputInterface;

trait ReceivesInput
{

    /** @var InputInterface */
    public $input;

    /**
     * Get an argument from the input list.
     *
     * @param string $key
     * @return mixed
     */
    protected function argument($key)
    {
        return $this->input->getArgument($key);
    }

    /**
     * Get an option from the input list.
     *
     * @param string $key
     * @return mixed
     */
    protected function option($key)
    {
        return $this->input->getOption($key);
    }

    /**
     * Check if the option was provided
     *
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name) : bool
    {
        return isset($this->input->getOptions()[$name])
            || in_array($name, $this->dynamicOptions);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasArgument(string $name) : bool
    {
        return isset($this->input->getArguments()[$name]);
    }

    /**
     * Returns the provided parameters for the command
     *
     * @param array $prepend
     * @param int $start
     * @return array
     */
    public function getParameters(array $prepend = [], int $start = 0) : array
    {
        return $this->input->getParameters($prepend, $start);
    }

}
