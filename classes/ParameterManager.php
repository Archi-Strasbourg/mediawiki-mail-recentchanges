<?php

namespace MediawikiMailRecentChanges;

use League\CLImate\CLImate;

class ParameterManager
{
    private CLImate $climate;

    public function __construct(CLImate $climate)
    {
        $this->climate = $climate;
    }

    public function get($parameter)
    {
        return $_GET[$parameter] ?? $this->climate->arguments->get($parameter);
    }
}
