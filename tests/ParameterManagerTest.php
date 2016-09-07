<?php

namespace MediawikiMailRecentChanges\Test;

use League\CLImate\CLImate;
use MediawikiMailRecentChanges\ParameterManager;

class ParameterManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $params = new ParameterManager(new CLImate());
        $this->assertNull($params->get('foo'));
        $_GET['foo'] = 'bar';
        $this->assertEquals('bar', $params->get('foo'));
    }
}
