<?php
namespace MediawikiMailRecentChanges;

use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Html2Text\Html2Text;

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
                        array(
                            'token'=>$this->token,
                            'target'=>$user,
                            'subject'=>$title,
                            'text'=>$plaintext->getText(),
                            'html'=>$html
                        )
                    )
            );
            $this->logger->info('E-mail sent to '.$user);
            return true;
        } catch (\Mediawiki\Api\UsageException $e) {
            $this->logger->error("Can't send e-mail to ".$user);
            return false;
        }
    }
}
