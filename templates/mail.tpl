<!DOCTYPE HTML>
<html lang="{$wiki.lang}">
    <head>
        <meta charset="UTF-8" />
        <title>{$title}</title>
    </head>
    <body>
        <p>
            Bonjour,<br/>
            Voici les pages qui ont été créées ou modifiées cette semaine sur <a href="{$wiki.url}" target="_blank">{$wiki.name}</a>&nbsp;:
        </p>

        {foreach $changeLists as $namespace=>$recentChanges}
            {foreach $recentChanges as $groupName=>$group}
                {if $groupName != '*'}
                    <h3>{$groupName}</h3>
                {else if $namespace != null}
                    <h3>{$namespace}s</h3>
                {/if}
                {if isset($group.new)}
                    <h4>Nouvelles pages&nbsp;:</h4>
                    <ul>
                        {foreach $group.new as $change}
                            <li><a href="{$wiki.url}{$change.title|escape:url}" target="_blank">{$change.shortTitle|replace:($namespace|cat:':'):''}</a></li>
                        {/foreach}
                    </ul>
                {/if}
                {if isset($group.edit)}
                    <h4>Pages modifiées&nbsp;:</h4>
                    <ul>
                        {foreach $group.edit as $change}
                            <li><a href="{$wiki.url}{$change.title|escape:url}" target="_blank">{$change.shortTitle|replace:($namespace|cat:':'):''}</a></li>
                        {/foreach}
                    </ul>
                {/if}
                <br />
            {/foreach}
        {/foreach}
</body>
