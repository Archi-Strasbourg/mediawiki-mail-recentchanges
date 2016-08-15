<?php

namespace MediawikiMailRecentChanges;

use League\CLImate\CLImate;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\ApiUser;

require_once __DIR__.'/vendor/autoload.php';

$smarty = new \Smarty();
$climate = new CLImate();
$params = new ParametersManager($climate);

if (php_sapi_name() == 'cli') {
    $climate->arguments->add(
        array(
            'help'=>array(
                'description'=>'Display help',
                'noValue'=>true,
                'prefix'=>'h',
                'longPrefix'=>'help'
            )
        )
    );
    $climate->arguments->parse();

    $climate->arguments->add(
        array(
            'apiUrl'=>array(
                'description'=>'MediaWiki API URL',
                'required'=>true,
                'prefix'=>'api',
                'longPrefix'=>'api-url'
            ),
            'username'=>array(
                'description'=>'MediaWiki username',
                'required'=>true,
                'prefix'=>'u',
                'longPrefix'=>'username'
            ),
            'password'=>array(
                'description'=>'MediaWiki password',
                'required'=>true,
                'prefix'=>'p',
                'longPrefix'=>'password'
            ),
            'debug'=>array(
                'description'=>'Output debug info',
                'noValue'=>true,
                'prefix'=>'d',
                'longPrefix'=>'debug'
            )
        )
    );
    if ($climate->arguments->get('help')) {
        $climate->usage();
        die;
    }
    $climate->arguments->parse();
}

$api = MediawikiApi::newFromApiEndpoint($params->get('apiUrl'));
$api->login(
    new ApiUser(
        $params->get('username'),
        $params->get('password')
    )
);

$recentchanges = $api->getRequest(
    FluentRequest::factory()
        ->setAction('query')
        ->addParams(
            array(
                'list'=>'recentchanges',
                'rcnamespace'=>4000,
                'rctype'=>'edit',
                'rctoponly'=>true,
                'rclimit'=>500,
                'rcprop'=>'title|timestamp|ids'
            )
        )
);

$smarty->assign('recentchanges', $recentchanges['query']['recentchanges']);

$users = $api->getRequest(
    FluentRequest::factory()
        ->setAction('query')
        ->addParams(
            array(
                'list'=>'allusers',
                'aulimit'=>5000
            )
        )
);

if (php_sapi_name() == 'apache2handler') {
    $smarty->display('mail_html.tpl');
} else {
    $logger = new Logger($climate);
    foreach ($users['query']['allusers'] as $user) {
        try {
            $result = $api->postRequest(
                FluentRequest::factory()
                    ->setAction('emailuser-html')
                    ->addParams(
                        array(
                            'token'=>$api->getToken('email'),
                            'target'=>$user['name'],
                            'subject'=>'Test',
                            'text'=>$smarty->fetch('mail_text.tpl'),
                            'html'=>$smarty->fetch('mail_html.tpl')
                        )
                    )
            );
            $logger->info('Email sent to '.$user['name']);
        } catch (\Mediawiki\Api\UsageException $e) {
            $logger->error("Can't send email to ".$user['name']);
        }
    }
}
