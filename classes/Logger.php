<?php

namespace MediawikiMailRecentChanges;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{

    private $climate;

    public function __construct($climate)
    {
        $this->climate = $climate;
    }

    public function log($level, $message, array $context = array())
    {
        if ($this->climate->arguments->get('debug')) {
            switch ($level) {
                case 'info':
                    $this->climate->green($message);
                    break;
                case 'error':
                    $this->climate->red($message);
                    break;
                default:
                    $this->climate->output($message);
            }
        }
    }
}
