<?php

namespace MediawikiMailRecentChanges;

use League\CLImate\CLImate;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    private $climate;
    private $debug = false;

    public function __construct(CLImate $climate)
    {
        $this->climate = $climate;
        if ($this->climate->arguments->get('debug')) {
            $this->debug = true;
        }
    }

    public function log($level, $message, array $context = [])
    {
        if ($this->debug) {
            switch ($level) {
                case 'info':
                    $this->climate->info($message);
                    break;
                case 'error':
                    $this->climate->error($message);
                    break;
                default:
                    $this->climate->output($message);
            }
        }

        return $message;
    }
}
