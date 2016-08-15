<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>{$title}</title>
    </head>
    <body>
    <ul>
        {foreach $recentchanges as $change}
            <li>{$change.title}</li>
        {/foreach}
    </ul>
</body>
