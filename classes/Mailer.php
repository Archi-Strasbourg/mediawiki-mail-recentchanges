<?php

namespace MediawikiMailRecentChanges;

use Html2Text\Html2Text;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;

class Mailer
{
    private $api;
    private $emailApiName;
    private $token;
    private $logger;

    public function __construct(MediawikiApi $api, $emailApiName, Logger $logger)
    {
        $this->api = $api;
        $this->emailApiName = $emailApiName;
        $this->token = $api->getToken('email');
        $this->logger = $logger;
    }

    public function send($user, $html, $title)
    {
        $plaintext = new Html2Text($html);
        try {
            $this->api->postRequest(
                FluentRequest::factory()
                    ->setAction($this->emailApiName)
                    ->addParams(
                        [
                            'token'   => $this->token,
                            'target'  => $user,
                            'subject' => $title,
                            'text'    => $plaintext->getText(),
                            'html'    => $html,
                        ]
                    )
            );
            $this->logger->info('E-mail sent to '.$user);

            return true;
        } catch (\Mediawiki\Api\UsageException $e) {
            $expectedMessage = 'The user has not specified a valid email address, '.
                'or has chosen not to receive email from other users';
            if ($e->getMessage() == $expectedMessage) {
                $this->logger->error("Can't send e-mail to ".$user);

                return false;
            } else {
                throw($e);
            }
        }
    }
}
