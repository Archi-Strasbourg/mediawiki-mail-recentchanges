<?php

namespace MediawikiMailRecentChanges;

use Html2Text\Html2Text;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\UsageException;
use Smarty;
use SmartyException;

class Mailer
{
    private MediawikiApi $api;
    private string $emailApiName;
    private string $token;
    private Logger $logger;

    /**
     * @param MediawikiApi $api
     * @param string $emailApiName
     * @param Logger $logger
     * @param Smarty $smarty
     */
    public function __construct(MediawikiApi $api, string $emailApiName, Logger $logger, private readonly Smarty $smarty)
    {
        $this->api = $api;
        $this->emailApiName = $emailApiName;
        $this->token = $api->getToken('email');
        $this->logger = $logger;
    }

    /**
     * @param string $user
     * @param string $title
     * @return void
     * @throws SmartyException
     */
    public function send(string $user, string $title): void
    {
        $unsubscribeInfo = $this->api->getRequest(
            FluentRequest::factory()
                ->setAction('query')
                ->addParams(
                    [
                        'prop' => 'archiUnsubscribeLink',
                        'user' => $user
                    ]
                )
        );
        $this->smarty->assign('unsubscribeUrl', $unsubscribeInfo['url']);
        $html = $this->smarty->fetch('mail.tpl');
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

            return;
        } catch (UsageException $e) {
            $this->logger->error("Can't send e-mail to ".$user.': '.$e->getMessage());

            return;
        }
    }
}
