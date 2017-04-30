<?php

namespace MediawikiMailRecentChanges\Test;

use MediawikiMailRecentChanges\ChangeList;

class ChangeListTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAll()
    {
        $params = new ChangeList(
            [
                ['title' => 'Foo (Baz)'],
                ['title' => 'Bar (Baz)'],
            ],
            [
                ['title' => 'Foo (Baz)'],
            ]
        );
        $this->assertEquals(
            [
                '*' => [
                    'edit' => [
                        ['title' => 'Bar (Baz)', 'shortTitle' => 'Bar (Baz)'],
                    ],
                    'new' => [
                        ['title' => 'Foo (Baz)', 'shortTitle' => 'Foo (Baz)'],
                    ],
                ],
            ],
            $params->getAll()
        );
        $this->assertEquals(
            [
                'Baz' => [
                    'edit' => [
                        ['title' => 'Bar (Baz)', 'shortTitle' => 'Bar'],
                    ],
                    'new' => [
                        ['title' => 'Foo (Baz)', 'shortTitle' => 'Foo'],
                    ],
                ],
            ],
            $params->getAll('parentheses')
        );
    }
}
