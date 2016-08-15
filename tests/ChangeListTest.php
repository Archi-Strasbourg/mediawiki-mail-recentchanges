<?php
namespace MediawikiMailRecentChanges\Test;

use MediawikiMailRecentChanges\ChangeList;

class ChangeListTest extends \PHPUnit_Framework_TestCase
{

    public function testGetAll()
    {
        $params = new ChangeList(
            array(
                array('title'=>'Foo (Baz)'),
                array('title'=>'Bar (Baz)')
            ),
            array(
                array('title'=>'Foo (Baz)')
            )
        );
        $this->assertEquals(
            array(
                '*'=>array(
                    'edit'=>array(
                        array('title'=>'Bar (Baz)', 'shortTitle'=>'Bar (Baz)')
                    ),
                    'new'=>array(
                        array('title'=>'Foo (Baz)', 'shortTitle'=>'Foo (Baz)')
                    )
                )
            ),
            $params->getAll()
        );
        $this->assertEquals(
            array(
                'Baz'=>array(
                    'edit'=>array(
                        array('title'=>'Bar (Baz)', 'shortTitle'=>'Bar')
                    ),
                    'new'=>array(
                        array('title'=>'Foo (Baz)', 'shortTitle'=>'Foo')
                    )
                )
            ),
            $params->getAll('parentheses')
        );
    }
}
