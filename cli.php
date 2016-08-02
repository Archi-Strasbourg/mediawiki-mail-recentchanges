<?php
use League\CLImate\CLImate;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\ApiUser;

require_once __DIR__.'/vendor/autoload.php';

$climate = new CLImate();
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
        )
    )
);
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

$api->postRequest(
    FluentRequest::factory()
        ->setAction('emailuser')
        ->addParams(
            array(
                'token'=>$api->getToken('email'),
                'target'=>'Rudloff',
                'subject'=>'Test',
                'text'=>'Adresses modifiées :'.PHP_EOL.
                '* 22 rue de Bâle : http://localhost/archi-mediawiki/index.php/Adresse:22_rue_de_B%C3%A2le_(Strasbourg)'
            )
        )
);
