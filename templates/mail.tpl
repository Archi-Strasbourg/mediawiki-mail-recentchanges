<!DOCTYPE HTML>
<html lang="{$wiki.lang}">
    <head>
        <meta charset="UTF-8" />
        <title>{$title}</title>
    </head>
    <body>
        <p>
            Bonjour,<br/>
            Voici les pages qui ont été créées modifiées cette semaine sur <a href="{$wiki.url}" target="_blank">{$wiki.name}</a>&nbsp;:
        </p>

        <h3>Nouvelles pages&nbsp;:</h3>
        <ul>
            {foreach $newArticles as $change}
                <li><a href="{$wiki.url}{$change.title|escape:url}" target="_blank">{$change.title|replace:$wiki.namespace:''}</a></li>
            {/foreach}
        </ul>

        <h3>Pages modifiées&nbsp;:</h3>
        <ul>
            {foreach $recentChanges as $change}
                <li><a href="{$wiki.url}{$change.title|escape:url}" target="_blank">{$change.title|replace:$wiki.namespace:''}</a></li>
            {/foreach}
        </ul>
</body>
