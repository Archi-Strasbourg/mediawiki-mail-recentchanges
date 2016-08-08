<?php
namespace MediawikiMailRecentChanges\Test;

use MediawikiMailRecentChanges\Logger;
use League\CLImate\CLImate;

class LoggerTest extends \PHPUnit_Framework_TestCase
{

    public function testLog()
    {
        $climate = $this->createMock('League\CLImate\CLImate');
        $climate->arguments = $this->createMock('League\CLImate\Argument\Manager');
        $climate->arguments->method('get')->willReturn(true);
        $logger = new Logger($climate);
        $logger->error('test');
        $logger->info('test');
        $logger->notice('test');
    }
}
