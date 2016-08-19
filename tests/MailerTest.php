<?php
namespace MediawikiMailRecentChanges\Test;

use MediawikiMailRecentChanges\Mailer;
use MediawikiMailRecentChanges\Logger;
use Mediawiki\Api\MediawikiApi;
use League\CLImate\CLImate;

class MailerTest extends \PHPUnit_Framework_TestCase
{

    public function testSend()
    {
        $api = $this->createMock('Mediawiki\Api\MediawikiApi');
        $mailer = new Mailer(
            $api,
            'emailuser',
            $logger = new Logger(new CLImate())
        );
        $this->assertTrue(
            $mailer->send(
                'Rudloff',
                'Et eveniet qui rem sed omnis voluptatem aut. A praesentium nam numquam impedit ipsam est est. '.
                'Voluptatem et esse quia ipsa et voluptatem nemo facilis. '.
                'Enim eaque ullam est doloribus officia minus non. '.
                'Minima ut explicabo quae deleniti autem occaecati.',
                'Test'
            )
        );
    }

    public function testSendWithRealApi()
    {
        $mailer = new Mailer(
            MediawikiApi::newFromApiEndpoint('https://fr.wikipedia.org/w/api.php'),
            'emailuser',
            $logger = new Logger(new CLImate())
        );
        $this->assertFalse(
            $mailer->send(
                'Rudloff',
                'Et eveniet qui rem sed omnis voluptatem aut. A praesentium nam numquam impedit ipsam est est. '.
                'Voluptatem et esse quia ipsa et voluptatem nemo facilis. '.
                'Enim eaque ullam est doloribus officia minus non. '.
                'Minima ut explicabo quae deleniti autem occaecati.',
                'Test'
            )
        );
    }
}
