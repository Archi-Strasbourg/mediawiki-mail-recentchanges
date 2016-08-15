<?php
namespace MediawikiMailRecentChanges\Test;

use MediawikiMailRecentChanges\ParametersManager;
use League\CLImate\CLImate;

class ParametersManagerTest extends \PHPUnit_Framework_TestCase
{

    public function testGet()
    {
        $params = new ParametersManager(new CLImate());
        $this->assertNull($params->get('foo'));
        $_GET['foo'] = 'bar';
        $this->assertEquals('bar', $params->get('foo'));
    }
}
