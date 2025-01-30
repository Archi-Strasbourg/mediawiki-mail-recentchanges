<!DOCTYPE HTML>
<html lang="{$wiki.lang}">
    <head>
        <meta charset="UTF-8" />
        <title>{$title}</title>
        <style type="text/css">
            {literal}
                h4{
                    margin-left:1.5em; 
                    width:max-content; 
                    border-bottom: 1px dotted #d96e5d;
                }
                h3{
                    margin-left:0em; 
                    width:max-content; 
                    border-bottom: 2px solid #d96e5d;
                }
            {/literal}
        </style>

    </head>
    <body style='margin:0px auto; background-color:#fafafa;font-family: "Open Sans",Helvetica,Roboto,Arial,sans-serif;font-weight: normal;color: #34414a; '>
        <a href="{$wiki.url}" target="_blank"><img src="{$wiki.url}skins/archi-wiki/resources/img/logo_archi_wiki.png" alt="Archi-Wiki Logo" height="80"/></a>
        <p> Bonjour,</p>
        <p>{$intro}</p>
        <p>
            Voici les pages qui ont été créées ou modifiées cette semaine sur <a href="{$wiki.url}" target="_blank" style="color: #a65447">{$wiki.name}</a>.
        </p>
        {foreach $changeLists as $namespace=>$recentChanges}
            {foreach $recentChanges as $groupName=>$group}
                {if $groupName != '*'}
                    <h3>{$groupName}</h3>
                {else if $namespace != null}
                    <h3>{$namespace}s</h3>
                {/if}
                <table style="width:100%"><tr>
                    <td>
                        <table align="left"><tr><td>
                            {if isset($group.new)}
                                <h4>Nouvelles pages&nbsp;:</h4>
                                <ul>
                                    {foreach $group.new as $change}
                                        <li><a href="{$wiki.url}{$change.title|escape:url}" target="_blank" style="color: #a65447;">{$change.shortTitle|replace:($namespace|cat:':'):''}{if isset($change.quartier)} ({$change.quartier}){/if}</a>  {$val=$change.newlen-$change.oldlen} {if $val>500} <span style="color: #006400; font-weight: bold;">({$val} nouveaux caractères)</span> {elseif $val > 1} <span style="color: #006400;">({$val} nouveaux caractères)</span> {elseif $val > -1} <span style="color: black;">({$val|replace:'-':''} nouveau caractère)</span> {elseif $val > -2} <span style="color: black;">({$val|replace:'-':''} caractère supprimé)</span> {elseif $val > -500} <span style="color: #8b0000;">({$val|replace:'-':''} caractères supprimés)</span> {else} <span style="color: #8b0000; font-weight: bold;">({$val|replace:'-':''} caractères supprimés)</span> {/if}</li>
                                    {/foreach}
                                </ul>
                            {/if}
                            {if isset($group.edit)}
                                <h4>Pages modifiées&nbsp;:</h4>
                                <ul>
                                    {foreach $group.edit as $change}
                                        <li><a href="{$wiki.url}{$change.title|escape:url}" target="_blank" style="color: #a65447;">{$change.shortTitle|replace:($namespace|cat:':'):''}{if isset($change.quartier)} ({$change.quartier}){/if}</a>  {$val=$change.newlen-$change.oldlen} {if $val>500} <span style="color: #006400; font-weight: bold;">({$val} nouveaux caractères)</span> {elseif $val > 1} <span style="color: #006400;">({$val} nouveaux caractères)</span> {elseif $val > -1} <span style="color: black;">({$val|replace:'-':''} nouveau caractère)</span> {elseif $val > -2} <span style="color: black;">({$val|replace:'-':''} caractère supprimé)</span> {elseif $val > -500} <span style="color: #8b0000;">({$val|replace:'-':''} caractères supprimés)</span> {else} <span style="color: #8b0000; font-weight: bold;">({$val|replace:'-':''} caractères supprimés)</span> {/if}</li>
                                    {/foreach}
                                </ul>
                            {/if}
                        </td></tr></table>
                    
                    {if isset($group.biggestChange)}<div style="float:right;"><a href="{$wiki.url}{$group.biggestChange|escape:url}" style="color:#34414a;text-decoration: none; margin-right:0px;text-align:center;"><img src="{$group.image}" height="250px" style="margin:auto;"/><p style="margin:0px;color:#34414a;text-decoration: none;">{$group.biggestChange}</p></a></div>{/if}</td>
                </tr></table>
            {/foreach}
        {/foreach}
        <p>Pour ne plus recevoir les alertes mail, il vous suffit de vous rendre sur <a href="{$wiki.url}DésinscriptionAlerteMail" target="_blank">cette page</a>.</p>
</body>
