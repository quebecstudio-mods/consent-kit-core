<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * The inline script every page needs in `<head>`, ahead of any tracker: it
 * reads the consent cookie and seeds the Matomo and Google queues refused.
 *
 * The script itself lives in `resources/bootstrap.js`, shared by every
 * wrapper; only where it is registered differs.
 */
final class Bootstrap
{
    /** @param array $config as returned by Resolver::bootstrapConfig() */
    public static function script(array $config): string
    {

        $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR);

        return str_replace('__CONF__', $json, (string)file_get_contents(dirname(__DIR__) . '/resources/bootstrap.js'));
    }
}
