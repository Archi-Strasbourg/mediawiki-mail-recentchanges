<?php
namespace MediawikiMailRecentChanges\Test;

use MediawikiMailRecentChanges\ParameterManager;
use League\CLImate\CLImate;

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
