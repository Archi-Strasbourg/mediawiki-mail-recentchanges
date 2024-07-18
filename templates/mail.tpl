<!DOCTYPE HTML>
<html lang="{$wiki.lang}">
    <head>
        <meta charset="UTF-8" />
        <title>{$title}</title>

    </head>
    <body style='margin:0px auto; background-color:#fafafa; font-family: "Open Sans",Helvetica,Roboto,Arial,sans-serif; font-weight: normal; color: #34414a;'>
        <a href="{$wiki.url}" target="_blank"><img src="{$wiki.url}skins/archi-wiki/resources/img/logo_archi_wiki.png" alt="Archi-Wiki Logo" height="80"/></a>
        <p> Bonjour,</p>
        <p>{$intro}</p>
        <p>
            Voici les pages qui ont été créées ou modifiées cette semaine sur <a href="{$wiki.url}" target="_blank" style="color: #a65447">{$wiki.name}</a>.
        </p>
        {foreach $changeLists as $namespace=>$recentChanges}
            {foreach $recentChanges as $groupName=>$group}
                {if $groupName != '*'}
                    <h3 style="margin-left:0em; width:max-content; border-bottom: 2px solid #d96e5d;">{$groupName}</h3>
                {else if $namespace != null}
                    <h3 style="margin-left:0em; width:max-content; border-bottom: 2px solid #d96e5d;">{$namespace}s</h3>
                {/if}
                {if isset($group.new)}
                    <h4 style="margin-left:1.5em; width:max-content; border-bottom: 1px dotted #d96e5d;">Nouvelles pages&nbsp;:</h4>
                    <ul>
                        {foreach $group.new as $change}
                            <li><a href="{$wiki.url}{$change.title|escape:url}" target="_blank" style="color: #a65447;">{$change.shortTitle|replace:($namespace|cat:':'):''}{if isset($change.quartier)} ({$change.quartier}){/if}</a>  {$val=$change.newlen-$change.oldlen} {if $val>500} <span style="color: #006400; font-weight: bold;">({$val} nouveaux caractères)</span> {elseif $val > 1} <span style="color: #006400;">({$val} nouveau caractère)</span> {elseif $val > -1} <span style="color: black;">({abs($val)} nouveau caractère)</span> {elseif $val > -2} <span style="color: black;">({abs($val)} caractère supprimé)</span> {elseif $val > -500} <span style="color: #8b0000;">({abs($val)} caractères supprimés)</span> {else} <span style="color: #8b0000; font-weight: bold;">({abs($val)} caractères supprimés)</span> {/if}</li>
                        {/foreach}
                    </ul>
                {/if}
                {if isset($group.edit)}
                    <h4 style="margin-left:1.5em; width:max-content; border-bottom: 1px dotted #d96e5d;">Pages modifiées&nbsp;:</h4>
                    <ul>
                        {foreach $group.edit as $change}
                            <li><a href="{$wiki.url}{$change.title|escape:url}" target="_blank" style="color: #a65447;">{$change.shortTitle|replace:($namespace|cat:':'):''}{if isset($change.quartier)} ({$change.quartier}){/if}</a>  {$val=$change.newlen-$change.oldlen} {if $val>500} <span style="color: #006400; font-weight: bold;">({$val} nouveaux caractères)</span> {elseif $val > 1} <span style="color: #006400;">({$val} nouveau caractère)</span> {elseif $val > -1} <span style="color: black;">({abs($val)} nouveau caractère)</span> {elseif $val > -2} <span style="color: black;">({abs($val)} caractère supprimé)</span> {elseif $val > -500} <span style="color: #8b0000;">({abs($val)} caractères supprimés)</span> {else} <span style="color: #8b0000; font-weight: bold;">({abs($val)} caractères supprimés)</span> {/if}</li>
                        {/foreach}
                    </ul>
                {/if}
            {/foreach}
        {/foreach}
        <p>Pour ne plus recevoir les alertes mail, il vous suffit de vous connecter à votre profil <a href="{$wiki.url}Spécial:Préférences" target="_blank">{$wiki.name}</a>.</p>
</body>
