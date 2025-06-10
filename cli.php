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


//paramètre les fonctions de template
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

//paramètre l'api
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

//fonction pour trouver l'index d'un élément dans un tableau de pageid ou -1 si l'élément n'est pas trouvé
function findInArray($array, $id)
{
    foreach ($array as $i => $item) {
        if ($item['pageid'] == $id) {
            return $i;
        }
    }
    return -1;
}

$changeLists = [];
foreach (array_map('intval', explode(',', $params->get('namespaces'))) as $namespace) { //deux namespase : Personne et adresse
    $endDate = new \DateTime();
    $endDate->sub(new \DateInterval('P1W')); //cherche sur la dernière semaine

    //toutes les modifs et nouvelles pages
    $recentChangesTMP = $api->getRequest(
        FluentRequest::factory()
            ->setAction('query')
            ->addParams(
                [
                    'list'        => 'recentchanges',
                    'rcnamespace' => $namespace,
                    'rctype'      => 'edit|new',
                    'rcdir'       => 'newer',
                    'rclimit'     => 1000,
                    'rcstart'       => $endDate->format('r'),
                    'rcprop'      => 'title|timestamp|ids|sizes',
                    'rcshow'      => '!bot',
                ]
            )
    );

    //cumule les changements par page et sépare les nouveaux articles et les articles modifiés
    $recentChanges =[];
    $newArticles=[];
    $newArticles['query']['recentchanges']=[];
    $recentChanges['query']['recentchanges']=[];
    foreach($recentChangesTMP['query']['recentchanges'] as $change){
        
        $indice=findInArray($recentChanges['query']['recentchanges'], $change['pageid']);
        if($indice==-1){
            $indice2=findInArray($newArticles['query']['recentchanges'], $change['pageid']);
            if($indice2==-1 && $change['type']=='new'){
                $newArticles['query']['recentchanges'][] = $change;
            } else if ($indice2==-1){
                $recentChanges['query']['recentchanges'][] = $change;
            } else {
                $newArticles['query']['recentchanges'][$indice2]['newlen'] = $change['newlen'];
            }
        } else {
            $recentChanges['query']['recentchanges'][$indice]['newlen'] = $change['newlen'];
        }
    }

    //créer la liste qui sera utilisé par le template
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


//cherche tout les utilisateurs sauf ceux désinscrits de l'alerte mail
$users = $api->getRequest(
    FluentRequest::factory()
        ->setAction('query')
        ->addParams(
            [
                'list'    => 'allusers',
                'aulimit' => 5000,
                'auprop'  => 'blockinfo',
                'auexcludegroup' => 'noAlerteMail'
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

//récupère l'url du site
$baseUrl = str_replace(
    $siteInfo['query']['general']['mainpage'],
    '',
    urldecode($siteInfo['query']['general']['base'])
);

//récupère le text d'intro de l'alerte mail disponible à la page donnée en paramètre "intro"
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



//cherche le plus gros changement par ville
foreach($changeLists['Adresse'] as &$ville){
    $biggestChangeNum=-99999;
    $biggestChange='fail';
    if(isset($ville['edit'])){
        foreach($ville['edit'] as &$adresse){
            if($adresse['newlen']-$adresse['oldlen']>$biggestChangeNum){
                $biggestChangeNum=$adresse['newlen']-$adresse['oldlen'];
                $biggestChange=$adresse['title'];
            }

            //on en profite pour récupérer le quartier de l'adresse
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
                    if (($categories[2] != $categories[0]) && ($categories[2] != $categories[1])) {
                        $colonIndex = strpos($categories[2], ':');
                        $parenthesisIndex = strrpos($categories[2], '_('.substr($categories[1], strpos($categories[1], ':')+1));
                        $adresse['quartier'] = substr($categories[2], $colonIndex + 1, $parenthesisIndex - $colonIndex - 1);
                    } else if (isset($categories[4]) && ($categories[2] == $categories[0])){
                        $colonIndex = strpos($categories[4], ':');
                        $parenthesisIndex = strrpos($categories[4], '_('.substr($categories[3], strpos($categories[3], ':')+1));
                        $adresse['quartier'] = substr($categories[4], $colonIndex + 1, $parenthesisIndex - $colonIndex - 1);
                    } 

                }
                    
            }
        }
        unset($adresse);
    }
    if(isset($ville['new'])){
        foreach($ville['new'] as &$adresse){
            if($adresse['newlen']-$adresse['oldlen']>$biggestChangeNum){
                $biggestChangeNum=$adresse['newlen']-$adresse['oldlen'];
                $biggestChange=$adresse['title'];
            }
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
                $categories = array_reverse($page['categories'] ?? []);
                if(isset($categories[2])){
                    if (($categories[2] != $categories[0]) && ($categories[2] != $categories[1])) {
                        $colonIndex = strpos($categories[2], ':');
                        $parenthesisIndex = strrpos($categories[2], '_('.substr($categories[1], strpos($categories[1], ':')+1));
                        $adresse['quartier'] = substr($categories[2], $colonIndex + 1, $parenthesisIndex - $colonIndex - 1);
                    } else if (isset($categories[4]) && ($categories[2] == $categories[0])){
                        $colonIndex = strpos($categories[4], ':');
                        $parenthesisIndex = strrpos($categories[4], '_('.substr($categories[1], strpos($categories[3], ':')+1));
                        $adresse['quartier'] = substr($categories[4], $colonIndex + 1, $parenthesisIndex - $colonIndex - 1);
                    } 

                }
            }
        }
        unset($adresse);
    }

    //récupère le lien de l'image du plus gros changement
    if($biggestChange!='fail'){
        $image = $api->getRequest(
            FluentRequest::factory()
                ->setAction('ask')
                ->addParams(
                    [
                        'query' => '[['.$biggestChange.']]|?Image principale|limit=1'
                    ]
                )
        );

        $imageInfo = $image['query']['results'][array_keys($image['query']['results'])[0]]['printouts']['Image principale'];
        if (isset($imageInfo[0])) {
            $image = $imageInfo[0]['fulltext'];
            $image = $api->getRequest(
                FluentRequest::factory()
                    ->setAction('parse')
                    ->addParams(
                        [
                            'text' => '[[' . $image . '|x250px]]' //250px de haut pour avoir une image de taille raisonnable
                        ]
                    )
            );
            $image = $image['parse']['text']['*'];
            if (strpos($image, 'Image-manquante') == false) {
                $imageSource = '';
                preg_match('/src="([^"]+)"/', $image, $matches); //récupère uniquement le lien de l'image
                if (isset($matches[1])) {
                    $imageSource = $matches[1];
                }
                if (substr($imageSource, 0, 4) === 'http') {
                    $ville['image'] = $imageSource;
                    $ville['biggestChange'] = $biggestChange;
                } else {
                    $ville['image'] = $baseUrl . $imageSource;
                    $ville['biggestChange'] = $biggestChange;
                }
            }
        }
    }
}
unset($ville); 

//do the biggest change for Personne
foreach($changeLists['Personne'] as &$Personne){
    $biggestChangeNum=-99999;
    $biggestChange='fail';
    if(isset($Personne['edit'])){
        foreach($Personne['edit'] as &$adresse){
            if($adresse['newlen']-$adresse['oldlen']>$biggestChangeNum){
                $biggestChangeNum=$adresse['newlen']-$adresse['oldlen'];
                $biggestChange=$adresse['title'];
            }
        }
        unset($adresse);
    }
    if(isset($Personne['new'])){
        foreach($Personne['new'] as &$adresse){
            if($adresse['newlen']-$adresse['oldlen']>$biggestChangeNum){
                $biggestChangeNum=$adresse['newlen']-$adresse['oldlen'];
                $biggestChange=$adresse['title'];
            }
        }
        unset($adresse);
    }
    if($biggestChange!='fail'){
        $image = $api->getRequest(
            FluentRequest::factory()
                ->setAction('ask')
                ->addParams(
                    [
                        'query' => '[['.$biggestChange.']]|?Image principale|limit=1'
                    ]
                )
        );
        if(empty($image['query']['results'][array_keys($image['query']['results'])[0]]['printouts']['Image principale'])) {
            echo "no image found for ".$biggestChange."\n";
        } else {
            $image= $image['query']['results'][array_keys($image['query']['results'])[0]]['printouts']['Image principale'][0]['fulltext'];
            $image=$api->getRequest(
                FluentRequest::factory()
                    ->setAction('parse')
                    ->addParams(
                        [
                            'text' => '[['.$image.']]'
                        ]
                    )
            );
            $image=$image['parse']['text']['*'];
            $imageSource = '';
            if(strpos($image, 'Image-manquante')==false){
                preg_match('/src="([^"]+)"/', $image, $matches);
                if (isset($matches[1])) {
                    $imageSource = $matches[1];
                }
                if(substr($imageSource, 0, 4) === 'http'){
                    $Personne['image'] = $imageSource;
                    $Personne['biggestChange'] = $biggestChange;
                } else {
                    $Personne['image'] = $baseUrl.$imageSource;
                    $Personne['biggestChange'] = $biggestChange;
                }
            }
        }
    }
}
unset($ville); 

//donne les paramètres au template
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

//envoie le mail avec le template
if (php_sapi_name() == 'apache2handler') {
    $smarty->display('mail.tpl');
} else {
    $target = $params->get('target');
    $mailer = new Mailer($api, $emailApiName, $logger, $smarty);
    if (isset($target)) {
        $mailer->send($target, $title);
    } else {
        foreach ($users['query']['allusers'] as $user) {
            if (!isset($user['blockid'])) {
                $mailer->send($user['name'], $title);
            }
        }
    }
}

echo "success";
