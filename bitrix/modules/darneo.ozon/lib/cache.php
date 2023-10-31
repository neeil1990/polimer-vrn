<?php

namespace Darneo\Ozon;

use Bitrix\Main\Entity\DataManager;

class Cache
{
    public static function clean(): void
    {
        $entitiesDataClasses = (new Configuration())->get('entitiesDataClasses');
        /** @var DataManager $entityDataClass */
        foreach ($entitiesDataClasses as $entityDataClass) {
            $entityDataClass::getEntity()->cleanCache();
        }
    }
}
