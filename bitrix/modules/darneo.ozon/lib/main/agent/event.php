<?php

namespace Darneo\Ozon\Main\Agent;

use CAdminNotify;
use Darneo\Ozon\Configuration;

class Event
{
    public static function onModuleUpdate($readyModules): void
    {
        if (is_array($readyModules) && in_array(Configuration::MODULE_NAME, $readyModules, true)) {
            CAdminNotify::DeleteByTag(Update::NOTIFY_TAG);
        }
    }
}