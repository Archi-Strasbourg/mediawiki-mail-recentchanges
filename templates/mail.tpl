<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>{$title}</title>
    </head>
    <body>
        <p>
            Bonjour,<br/>
            Voici les pages qui ont été créées modifiées cette semaine sur <a href="{$wiki.url}" target="_blank">{$wiki.name}</a>&nbsp;:
        </p>
        <ul>
            {foreach $recentchanges as $change}
                <li>{$change.title}</li>
            {/foreach}
        </ul>
</body>
