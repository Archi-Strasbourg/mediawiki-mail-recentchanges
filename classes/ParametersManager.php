<?php
namespace MediawikiMailRecentChanges;

use League\CLImate\CLImate;

class ParametersManager
{
    private $climate;

    public function __construct(CLImate $climate)
    {
        $this->climate = $climate;
    }

    public function get($parameter)
    {
        if (isset($_GET[$parameter])) {
            return $_GET[$parameter];
        } else {
            return $this->climate->arguments->get($parameter);
        }
    }
}
