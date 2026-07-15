<?php

namespace MediawikiMailRecentChanges;

use Addwiki\Mediawiki\Api\Client\Action\Exception\UsageException;
use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;
use Addwiki\Mediawiki\Api\Client\MediaWiki;
use Html2Text\Html2Text;
use Smarty;
use SmartyException;

class Mailer
{
    private MediaWiki $api;
    private string $emailApiName;
    private string $token;
    private Logger $logger;

    /**
     * @param MediaWiki $api
     * @param string $emailApiName
     * @param Logger $logger
     * @param Smarty $smarty
     */
    public function __construct(MediaWiki $api, string $emailApiName, Logger $logger, private readonly Smarty $smarty)
    {
        $this->api = $api;
        $this->emailApiName = $emailApiName;
        $this->token = $api->action()->getToken('email');
        $this->logger = $logger;
    }

    /**
     * @param string $user
     * @param string $title
     * @return void
     * @throws SmartyException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function send(string $user, string $title): void
    {
        $actionApi = $this->api->action();
        $unsubscribeInfo = $actionApi->request(
            ActionRequest::simpleGet('query')
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
            $actionApi->request(
                ActionRequest::simplePost($this->emailApiName)
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
            $this->logger->info('E-mail sent to ' . $user);

            return;
        } catch (UsageException $e) {
            $this->logger->error("Can't send e-mail to " . $user . ': ' . $e->getMessage());

            return;
        }
    }
}
