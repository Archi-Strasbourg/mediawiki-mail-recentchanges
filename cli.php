<?php

namespace MediawikiMailRecentChanges;

use League\CLImate\CLImate;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\ApiUser;
use Html2Text\Html2Text;

require_once __DIR__.'/vendor/autoload.php';

$smarty = new \Smarty();
$climate = new CLImate();
$params = new ParameterManager($climate);

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
            'title'=>array(
                'description'=>'E-mail title',
                'required'=>true,
                'prefix'=>'t',
                'longPrefix'=>'title'
            ),
            'namespace'=>array(
                'description'=>'MediaWiki namespace',
                'required'=>false,
                'prefix'=>'ns',
                'longPrefix'=>'namespace',
                'castTo'=>'int'
            ),
            'groupby'=>array(
                'description'=>'Group recent changes. Possible values : parentheses',
                'required'=>false,
                'prefix'=>'g',
                'longPrefix'=>'groupby'
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
$namespace = $params->get('namespace');

$recentChanges = $api->getRequest(
    FluentRequest::factory()
        ->setAction('query')
        ->addParams(
            array(
                'list'=>'recentchanges',
                'rcnamespace'=>$params->get('namespace'),
                'rctype'=>'edit',
                'rctoponly'=>true,
                'rclimit'=>500,
                'rcprop'=>'title|timestamp|ids'
            )
        )
);

$newArticles = $api->getRequest(
    FluentRequest::factory()
        ->setAction('query')
        ->addParams(
            array(
                'list'=>'recentchanges',
                'rcnamespace'=>$namespace,
                'rctype'=>'new',
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

$siteInfo = $api->getRequest(
    FluentRequest::factory()
        ->setAction('query')
        ->addParams(
            array(
                'meta'=>'siteinfo',
                'siprop'=>'general|namespaces|extensions'
            )
        )
);

$emailApiName = 'emailuser';
foreach ($siteInfo['query']['extensions'] as $extension) {
    if ($extension['name'] == 'EmailuserHtml') {
        $emailApiName = 'emailuser-html';
        break;
    }
}

$title = $params->get('title');
$changeList = new ChangeList($recentChanges['query']['recentchanges'], $newArticles['query']['recentchanges']);
if (isset($namespace)) {
    $namespacePrefix = $siteInfo['query']['namespaces'][$namespace]['canonical'].':';
} else {
    $namespacePrefix = '';
}
$smarty->assign(
    array(
        'recentChanges'=>$changeList->getAll($params->get('groupby')),
        'title'=>$title,
        'wiki'=>array(
            'name'=>$siteInfo['query']['general']['sitename'],
            'url'=>str_replace(
                $siteInfo['query']['general']['mainpage'],
                '',
                urldecode($siteInfo['query']['general']['base'])
            ),
            'lang'=>$siteInfo['query']['general']['lang'],
            'namespace'=>$namespacePrefix
        )
    )
);

if (php_sapi_name() == 'apache2handler') {
    $smarty->display('mail.tpl');
} else {
    $logger = new Logger($climate);
    $html = $smarty->fetch('mail.tpl');
    $token = $api->getToken('email');
    $plaintext = new Html2Text($html);
    foreach ($users['query']['allusers'] as $user) {
        try {
            $result = $api->postRequest(
                FluentRequest::factory()
                    ->setAction($emailApiName)
                    ->addParams(
                        array(
                            'token'=>$token,
                            'target'=>$user['name'],
                            'subject'=>$title,
                            'text'=>$plaintext->getText(),
                            'html'=>$html
                        )
                    )
            );
            $logger->info('E-mail sent to '.$user['name']);
        } catch (\Mediawiki\Api\UsageException $e) {
            $logger->error("Can't send e-mail to ".$user['name']);
        }
    }
}
