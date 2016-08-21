<?php

namespace MediawikiMailRecentChanges;

use League\CLImate\CLImate;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\ApiUser;

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
            'namespaces'=>array(
                'description'=>'MediaWiki namespaces',
                'required'=>false,
                'prefix'=>'ns',
                'longPrefix'=>'namespaces',
                'castTo'=>'string'
            ),
            'groupby'=>array(
                'description'=>'Group recent changes. Possible values : parentheses',
                'required'=>false,
                'prefix'=>'g',
                'longPrefix'=>'groupby'
            ),
            'nsgroupby'=>array(
                'description'=>'Namespaces for which we must group changes',
                'required'=>false,
                'prefix'=>'nsg',
                'longPrefix'=>'nsgroupby'
            ),
            'debug'=>array(
                'description'=>'Output debug info',
                'noValue'=>true,
                'prefix'=>'d',
                'longPrefix'=>'debug'
            ),
            'target'=>array(
                'description'=>'Send email to a specific user',
                'longPrefix'=>'target'
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

$changeLists = array();
foreach (array_map('intval', explode(',', $params->get('namespaces'))) as $namespace) {
    $recentChanges = $api->getRequest(
        FluentRequest::factory()
            ->setAction('query')
            ->addParams(
                array(
                    'list'=>'recentchanges',
                    'rcnamespace'=>$namespace,
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

    $changeList = new ChangeList($recentChanges['query']['recentchanges'], $newArticles['query']['recentchanges']);
    if (in_array($namespace, explode(',', $params->get('nsgroupby')))) {
        $changeLists[$siteInfo['query']['namespaces'][$namespace]['canonical']] =
            $changeList->getAll($params->get('groupby'));
    } elseif ($namespace == 0) {
        $changeLists[null] = $changeList->getAll();
    } else {
        $changeLists[$siteInfo['query']['namespaces'][$namespace]['canonical']] = $changeList->getAll();
    }
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

$emailApiName = 'emailuser';
foreach ($siteInfo['query']['extensions'] as $extension) {
    if ($extension['name'] == 'EmailuserHtml') {
        $emailApiName = 'emailuser-html';
        break;
    }
}

$title = $params->get('title');
$smarty->assign(
    array(
        'changeLists'=>$changeLists,
        'title'=>$title,
        'wiki'=>array(
            'name'=>$siteInfo['query']['general']['sitename'],
            'url'=>str_replace(
                $siteInfo['query']['general']['mainpage'],
                '',
                urldecode($siteInfo['query']['general']['base'])
            ),
            'lang'=>$siteInfo['query']['general']['lang']
        )
    )
);

if (php_sapi_name() == 'apache2handler') {
    $smarty->display('mail.tpl');
} else {
    $html = $smarty->fetch('mail.tpl');
    $target = $params->get('target');
    $mailer = new Mailer($api, $emailApiName, new Logger($climate));
    if (isset($target)) {
        $mailer->send($target, $html, $title);
    } else {
        foreach ($users['query']['allusers'] as $user) {
            $mailer->send($user['name'], $html, $title);
        }
    }
}
