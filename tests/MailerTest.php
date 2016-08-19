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
        $climate = $this->createMock('League\CLImate\CLImate');
        $climate->arguments = $this->createMock('League\CLImate\Argument\Manager');
        $climate->arguments->method('get')->willReturn(true);
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
