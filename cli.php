<?php

namespace MediawikiMailRecentChanges;

use League\CLImate\CLImate;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\ApiUser;

require_once __DIR__.'/vendor/autoload.php';

$climate = new CLImate();
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

$api = MediawikiApi::newFromApiEndpoint($climate->arguments->get('apiUrl'));
$api->login(
    new ApiUser(
        $climate->arguments->get('username'),
        $climate->arguments->get('password')
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

$html = $text = '';

foreach ($recentchanges['query']['recentchanges'] as $change) {
    $html .= '<li>'.$change['title'].'</li>';
    $text .= '* '.$change['title'].PHP_EOL;
}

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
                        'text'=>$text,
                        'html'=>$html
                    )
                )
        );
        $logger->info('Email sent to '.$user['name']);
    } catch (\Mediawiki\Api\UsageException $e) {
        $logger->error("Can't send email to ".$user['name']);
    }
}
