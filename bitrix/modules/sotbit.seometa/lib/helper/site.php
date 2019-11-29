<?php

namespace Sotbit\Seometa\Helper;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

class Site {
    public static function getList()
    {
        $sites = [];
        $rs = \Bitrix\Main\SiteTable::getList([
            'select' => [
                'SITE_NAME',
                'LID',
            ],
            'filter' => ['ACTIVE' => 'Y'],
        ]);
        while ($site = $rs->fetch()) {
            $sites[$site['LID']] = $site['SITE_NAME'];
        }

        return $sites;
    }
}