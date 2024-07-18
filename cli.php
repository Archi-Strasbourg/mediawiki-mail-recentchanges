<?php


namespace MediawikiMailRecentChanges;



use League\CLImate\CLImate;
use Mediawiki\Api\ApiUser;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\MediawikiFactory;
use Mediawiki\Api\UsageException;
use DateTime;
use Exception;
use User;

require_once __DIR__.'/vendor/autoload.php';



//Required on servers that don't have a default timezone in PHP settings
date_default_timezone_set('Europe/Paris');



$smarty = new \Smarty();
$smarty->setTemplateDir(__DIR__.'/templates/');
$smarty->setCompileDir(__DIR__.'/templates_c/');
$climate = new CLImate();
$params = new ParameterManager($climate);

if (php_sapi_name() == 'cli') {
    $climate->arguments->add(
        [
            'help' => [
                'description' => 'Display help',
                'noValue'     => true,
                'prefix'      => 'h',
                'longPrefix'  => 'help',
            ],
        ]
    );
    $climate->arguments->parse();

    $climate->arguments->add(
        [
            'apiUrl' => [
                'description' => 'MediaWiki API URL',
                'required'    => true,
                'prefix'      => 'api',
                'longPrefix'  => 'api-url',
            ],
            'username' => [
                'description' => 'MediaWiki username',
                'required'    => true,
                'prefix'      => 'u',
                'longPrefix'  => 'username',
            ],
            'password' => [
                'description' => 'MediaWiki password',
                'required'    => true,
                'prefix'      => 'p',
                'longPrefix'  => 'password',
            ],
            'title' => [
                'description' => 'E-mail title',
                'required'    => true,
                'prefix'      => 't',
                'longPrefix'  => 'title',
            ],
            'namespaces' => [
                'description' => 'MediaWiki namespaces',
                'required'    => false,
                'prefix'      => 'ns',
                'longPrefix'  => 'namespaces',
                'castTo'      => 'string',
            ],
            'groupby' => [
                'description' => 'Group recent changes. Possible values : parentheses',
                'required'    => false,
                'prefix'      => 'g',
                'longPrefix'  => 'groupby',
            ],
            'nsgroupby' => [
                'description' => 'Namespaces for which we must group changes',
                'required'    => false,
                'prefix'      => 'nsg',
                'longPrefix'  => 'nsgroupby',
            ],
            'debug' => [
                'description' => 'Output debug info',
                'noValue'     => true,
                'prefix'      => 'd',
                'longPrefix'  => 'debug',
            ],
            'target' => [
                'description' => 'Send email to a specific user',
                'longPrefix'  => 'target',
            ],
            'intro' => [
                'description' => 'Intro text',
                'longPrefix'  => 'intro',
            ],
        ]
    );
    if ($climate->arguments->get('help')) {
        $climate->usage();
        die;
    }
    $climate->arguments->parse();
}

$apiUrl = $params->get('apiUrl');
if (!isset($apiUrl)) {
    echo 'no api url';
    throw new \Exception('Missing API URL');
}

$api = MediawikiApi::newFromApiEndpoint($apiUrl);
try{
    $api->login(
    new ApiUser(
        $params->get('username'),
        $params->get('password')
    )
    );
} catch (UsageException $e) {
    echo "login : ".$e->getMessage();
    die;
}


$siteInfo = $api->getRequest(
    FluentRequest::factory()
        ->setAction('query')
        ->addParams(
            [
                'meta'   => 'siteinfo',
                'siprop' => 'general|namespaces|extensions',
            ]
        )
);

$changeLists = [];
foreach (array_map('intval', explode(',', $params->get('namespaces'))) as $namespace) {
    $endDate = new \DateTime();
    $endDate->sub(new \DateInterval('P1W'));
    $recentChanges = $api->getRequest(
        FluentRequest::factory()
            ->setAction('query')
            ->addParams(
                [
                    'list'        => 'recentchanges',
                    'rcnamespace' => $namespace,
                    'rctype'      => 'edit',
                    'rctoponly'   => true,
                    'rclimit'     => 500,
                    'rcend'       => $endDate->format('r'),
                    'rcprop'      => 'title|timestamp|ids|sizes',
                    'rcshow'      => '!bot',
                ]
            )
    );

    $newArticles = $api->getRequest(
        FluentRequest::factory()
            ->setAction('query')
            ->addParams(
                [
                    'list'        => 'recentchanges',
                    'rcnamespace' => $namespace,
                    'rctype'      => 'new',
                    'rclimit'     => 500,
                    'rcend'       => $endDate->format('r'),
                    'rcprop'      => 'title|timestamp|ids|sizes',
                ]
            )
    );

    $changeList = new ChangeList($recentChanges['query']['recentchanges'], $newArticles['query']['recentchanges']);

    if ($namespace == 0) {
        $changeLists[null] = $changeList->getAll();
    } elseif (in_array($namespace, explode(',', $params->get('nsgroupby')))) {
        $changeLists[$siteInfo['query']['namespaces'][$namespace]['canonical']] =
            $changeList->getAll($params->get('groupby'));
    } else {
        $changeLists[$siteInfo['query']['namespaces'][$namespace]['canonical']] = $changeList->getAll();
    }
}



$users = $api->getRequest(
    FluentRequest::factory()
        ->setAction('query')
        ->addParams(
            [
                'list'    => 'allusers',
                'aulimit' => 5000,
                'auprop'  => 'blockinfo'
            ]
        )
);

$emailApiName = 'emailuser';
foreach ($siteInfo['query']['extensions'] as $extension) {
    if ($extension['name'] == 'EmailuserHtml') {
        $emailApiName = 'emailuser-html';
        break;
    }
}

$logger = new Logger($climate);
$services = new MediawikiFactory($api);

$baseUrl = str_replace(
    $siteInfo['query']['general']['mainpage'],
    '',
    urldecode($siteInfo['query']['general']['base'])
);

$title = $params->get('title');
$introTitle = $params->get('intro');
$intro = '';
if (isset($introTitle)) {
    $introPage = $services->newPageGetter()->getFromTitle($introTitle);
    if ($introPage->getPageIdentifier()->getId() == 0) {
        $logger->error('Could not find intro page');
    } else {
        $introParsed = $services->newParser()->parsePage($introPage->getPageIdentifier());
        $intro = str_replace('href="/', 'href="'.$baseUrl, $introParsed['text']['*']);
    }
}

//this is for removing the last br that is useless and is always present in $intro because of the {{Alerte hebdo}} call in the alerts
$BRcount=1;
$BRcountTotal=substr_count($intro, '<br />');
$BRcountTotal+=substr_count($intro, '<br/>');
$BRcountTotal+=substr_count($intro, '<br>');
function test($test){
    global $BRcount;
    global $BRcountTotal;
    if($BRcount==$BRcountTotal){
        $test[0]='';
    }
    $BRcount++;
    return $test[0];	

}
$intro = preg_replace_callback('/<br\s*\/?>/', 'MediawikiMailRecentChanges\test', $intro);




foreach($changeLists['Adresse'] as &$ville){
    foreach($ville['edit'] as &$adresse){
        $categories = $api->getRequest(
            FluentRequest::factory()
                ->setAction('query')
                ->addParams(
                    [
                        'prop' => 'archiCategoryTree',
                        'titles' => $adresse['title']
                    ]
                )
        );

        foreach($categories['query']['pages'] as $page){
            $categories = array_reverse($page['categories']);
            if(isset($categories[2])){
                $colonIndex = strpos($categories[2], ':');
                $parenthesisIndex = strrpos($categories[2], '_(');
                $adresse['quartier'] = substr($categories[2], $colonIndex + 1, $parenthesisIndex - $colonIndex - 1);
            }
            if(isset($categories[3])  && !(substr($categories[3], strpos($categories[3], ':')+1, 5) === 'Autre')){
                $colonIndex = strpos($categories[3], ':');
                $parenthesisIndex = strpos($categories[3], '_('.$adresse['quartier']);
                $adresse['quartier'] .= ' > '.substr($categories[3], $colonIndex + 1, $parenthesisIndex - $colonIndex - 1);
            }
            if(isset($adresse['quartier'])){
                $adresse['quartier'] = str_replace('_', ' ', $adresse['quartier']);
                $adresse['quartier'] = html_entity_decode(preg_replace('/\\\\u([\da-fA-F]{4})/', '&#x$1;', $adresse['quartier']));
            }
        }
    }
    unset($adresse);
    foreach($ville['new'] as &$adresse){
        $categories = $api->getRequest(
            FluentRequest::factory()
                ->setAction('query')
                ->addParams(
                    [
                        'prop' => 'archiCategoryTree',
                        'titles' => $adresse['title']
                    ]
                )
        );
        
        foreach($categories['query']['pages'] as $page){
            $categories = array_reverse($page['categories']);
            
            if(isset($categories[2])){
                $colonIndex = strpos($categories[2], ':');
                $parenthesisIndex = strrpos($categories[2], '_(');
                $adresse['quartier'] = substr($categories[2], $colonIndex + 1, $parenthesisIndex - $colonIndex - 1);
            }
            if(isset($categories[3])  && !(substr($categories[3], strpos($categories[3], ':')+1, 5) === 'Autre')){
                $colonIndex = strpos($categories[3], ':');
                $parenthesisIndex = strpos($categories[3], '_('.$adresse['quartier']);
                $adresse['quartier'] .= ' > '.substr($categories[3], $colonIndex + 1, $parenthesisIndex - $colonIndex - 1);
            }
        }
    }
    unset($adresse);
}
unset($ville); 

$smarty->assign(
    [
        'changeLists' => $changeLists,
        'title'       => $title,
        'intro'       => $intro,
        'wiki'        => [
            'name' => $siteInfo['query']['general']['sitename'],
            'url'  => $baseUrl,
            'lang' => $siteInfo['query']['general']['lang'],
        ],
    ]
);

if (php_sapi_name() == 'apache2handler') {
    $smarty->display('mail.tpl');
} else {
    $html = $smarty->fetch('mail.tpl');
    $target = $params->get('target');
    $mailer = new Mailer($api, $emailApiName, $logger);
    if (isset($target)) {
        $mailer->send($target, $html, $title);
    } else {
        foreach ($users['query']['allusers'] as $user) {
            if (!isset($user['blockid'])) {
                $mailer->send($user['name'], $html, $title);
            }
        }
    }
}

echo "success";
