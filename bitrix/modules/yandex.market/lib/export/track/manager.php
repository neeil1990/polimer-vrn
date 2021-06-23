<?php

namespace Yandex\Market\Export\Track;

use Yandex\Market;

class Manager
{
    protected static $registeredElementChanges = [];
    protected static $sourceTypeRegistryCache = [];
    protected static $promoSetupCache = [];
    protected static $setupIblockCache = [];

    /**
     * Было ли зарегистрировано изменение элемента при обработке текущего процесса
     *
     * @param $elementType string
     * @param $elementId int|string
     * @param $sourceType string
     * @param $sourceParams array|null
     *
     * @return bool
     */
    public static function isElementChangeRegistered($elementType, $elementId, $sourceType, $sourceParams = null)
    {
        $changeKey = static::getElementChangeKey($elementType, $elementId, $sourceType, $sourceParams);

        return isset(static::$registeredElementChanges[$changeKey]);
    }

    /**
     * Изменение сущности
     *
     * @param $entityType
     * @param $entityId
     */
    public static function registerEntityChange($entityType, $entityId)
    {
        $setupList = static::convertEntityToSetupList($entityType, $entityId);

        foreach ($setupList as $setupId => $setupElementType)
        {
            Market\Export\Run\Manager::registerChange($setupId, $setupElementType, $entityId);
        }
    }

    /**
     * Регистрация изменения элемента для обработки профилей на основании зарегистрированных источников данных
     *
     * @param $elementType string
     * @param $elementId int|string
     * @param $sourceType string
     * @param $sourceParams array|null
     */
    public static function registerElementChange($elementType, $elementId, $sourceType, $sourceParams = null)
    {
        $changeKey = static::getElementChangeKey($elementType, $elementId, $sourceType, $sourceParams);

        if (!isset(static::$registeredElementChanges[$changeKey]))
        {
            static::$registeredElementChanges[$changeKey] = true;

            $registryList = static::getSourceTypeRegistryList($sourceType);
            $registryList = static::filterRegistryListByParams($registryList, $sourceParams);
            $setupList = static::convertRegistryListToSetupList($registryList, $elementType, $sourceType, $sourceParams);

            foreach ($setupList as $setupId => $setupElementType)
            {
                Market\Export\Run\Manager::registerChange($setupId, $setupElementType, $elementId);
            }
        }
    }

    /**
     * Внутренний ключ для хранения информации об изменении элемента
     *
     * @param $elementType string
     * @param $elementId int|string
     * @param $sourceType string
     * @param $sourceParams array|null
     *
     * @return string
     */
    protected static function getElementChangeKey($elementType, $elementId, $sourceType, $sourceParams)
    {
        return $elementType . ':' . $elementId . ':' . $sourceType . ':' . (!empty($sourceParams) ? serialize($sourceParams) : '');
    }

    /**
     * Список зарегистрированных сущностей для типа источника данных
     *
     * @param $sourceType string
     *
     * @return array
     */
    protected static function getSourceTypeRegistryList($sourceType)
    {
        $result = null;

        if (isset(static::$sourceTypeRegistryCache[$sourceType]))
        {
            $result = static::$sourceTypeRegistryCache[$sourceType];
        }
        else
        {
            $result = Registry::getTypeSources([ $sourceType ]);

            static::$sourceTypeRegistryCache[$sourceType] = $result;
        }

        return $result;
    }

    /**
     * Фильтруем список зарегистрированных сущностей на основании параметров события
     *
     * @param $registryList array
     * @param $sourceParams array|null
     *
     * @return array
     */
    protected static function filterRegistryListByParams($registryList, $sourceParams)
	{
		if ($sourceParams === null)
		{
			$result = $registryList;
		}
		else
		{
			$result = [];

			foreach ($registryList as $row)
			{
				if ($row['SOURCE_PARAMS'] == $sourceParams)
				{
					$result[] = $row;
				}
			}
        }

        return $result;
    }

    /**
     * Конвертируем список сущностей в список профилей и типов элементов
     *
     * @param $registryList array
     * @param $elementType string
     * @param $sourceType string
     * @param $sourceParams array|null
     *
     * @return array $setupId => $elementType
     */
    protected static function convertRegistryListToSetupList($registryList, $elementType, $sourceType, $sourceParams)
    {
        $result = [];
        $needResolveElementTypeSetupList = [];

        foreach ($registryList as $row)
        {
            switch ($row['ENTITY_TYPE'])
            {
                case Table::ENTITY_TYPE_SETUP:
                    $result[$row['ENTITY_ID']] = $elementType;

                    if (isset($needResolveElementTypeSetupList[$row['ENTITY_ID']]))
                    {
                        unset($needResolveElementTypeSetupList[$row['ENTITY_ID']]);
                    }
                break;

                case Table::ENTITY_TYPE_PROMO:
                    $promoSetupList = static::getPromoSetupList($row['ENTITY_ID']);

                    foreach ($promoSetupList as $setupId)
                    {
                        if (!isset($result[$setupId]))
                        {
                            $result[$setupId] = $elementType;

                            if ($elementType === Market\Export\Run\Manager::ENTITY_TYPE_OFFER)
                            {
                                $needResolveElementTypeSetupList[$setupId] = true;
                            }
                        }
                    }
                break;
            }
        }

        if (!empty($needResolveElementTypeSetupList))
        {
            $setupElementTypeList = static::resolveSetupElementTypeList(array_keys($needResolveElementTypeSetupList), $elementType, $sourceType, $sourceParams);

            foreach ($setupElementTypeList as $setupId => $setupElementType)
            {
                $result[$setupId] = $setupElementType;
            }
        }

        return $result;
    }


    /**
     * Конвертируем сущность в список профилей
     *
     * @param $entityType
     * @param $entityId
     *
     * @return array
     */
    protected static function convertEntityToSetupList($entityType, $entityId)
    {
        $result = [];

        switch ($entityType)
        {
            case Table::ENTITY_TYPE_PROMO:
                $promoSetupList = static::getPromoSetupList($entityId);

                foreach ($promoSetupList as $setupId)
                {
                    $result[$setupId] = Market\Export\Run\Manager::ENTITY_TYPE_PROMO;
                }
            break;
        }

        return $result;
    }

    /**
     * Список автоматически обновляемых профилей, связанных с промо
     *
     * @param $promoId int
     *
     * @return int[]
     */
    protected static function getPromoSetupList($promoId)
    {
        $result = null;

        if (isset(static::$promoSetupCache[$promoId]))
        {
            $result = static::$promoSetupCache[$promoId];
        }
        else
        {
            $result = [];

            $queryPromo = Market\Export\Promo\Table::getList([
                'filter' => [ '=ID' => $promoId ],
                'select' => [ 'SETUP_EXPORT_ALL' ]
            ]);

            if ($promo = $queryPromo->fetch())
            {
                if ($promo['SETUP_EXPORT_ALL'] === Market\Export\Promo\Table::BOOLEAN_Y)
                {
                    $querySetupList = Market\Export\Setup\Table::getList([
                        'filter' => [ '=AUTOUPDATE' => '1' ],
                        'select' => [ 'ID' ]
                    ]);

                    while ($setup = $querySetupList->fetch())
                    {
                        $result[$setup['ID']] = true;
                    }
                }
                else
                {
                    $querySetupLinkList = Market\Export\Promo\Internals\SetupLinkTable::getList([
                        'filter' => [
                            '=PROMO_ID' => $promoId,
                            '=SETUP.AUTOUPDATE' => '1'
                        ],
                        'select' => [ 'SETUP_ID' ]
                    ]);

                    while ($setupLink = $querySetupLinkList->fetch())
                    {
                        $result[$setupLink['SETUP_ID']] = true;
                    }
                }

                $result = array_keys($result);
            }

            static::$promoSetupCache[$promoId] = $result;
        }

        return $result;
    }

    /**
     * Переопределяем тип элемента для профилей (конвертация offer -> gift)
     *
     * @param $setupList int[]
     * @param $elementType string
     * @param $sourceType string
     * @param $sourceParams array|null
     *
     * @return array $setupId => $elementType
     */
    protected static function resolveSetupElementTypeList($setupList, $elementType, $sourceType, $sourceParams)
    {
        $result = [];

        if ($elementType === Market\Export\Run\Manager::ENTITY_TYPE_OFFER && !empty($sourceParams['IBLOCK_ID']))
        {
            $setupIblockList = static::getSetupIblockList($setupList);
            $elementIblockId = (int)$sourceParams['IBLOCK_ID'];

            foreach ($setupList as $setupId)
            {
                if (!empty($setupIblockList[$setupId]) && !in_array($elementIblockId, $setupIblockList[$setupId]))
                {
                    $result[$setupId] = Market\Export\Run\Manager::ENTITY_TYPE_GIFT;
                }
            }
        }

        return $result;
    }

    /**
     * Список используемых инфоблоков для профилей
     *
     * @param $setupList
     *
     * @return array $setupId => $iblockIdList
     */
    protected static function getSetupIblockList($setupList)
    {
        $result = [];
        $needFetch = [];

        foreach ($setupList as $setupId)
        {
            if (isset(static::$setupIblockCache[$setupId]))
            {
                $result[$setupId] = static::$setupIblockCache[$setupId];
            }
            else
            {
                $needFetch[] = $setupId;
            }
        }

        if (!empty($needFetch))
        {
            $queryIblockLinkList = Market\Export\IblockLink\Table::getList([
                'filter' => [ '=SETUP_ID' => $needFetch ],
                'select' => [ 'SETUP_ID', 'IBLOCK_ID' ]
            ]);

            while ($iblockLink = $queryIblockLinkList->fetch())
            {
                $setupId = (int)$iblockLink['SETUP_ID'];
                $iblockId = (int)$iblockLink['IBLOCK_ID'];

                if (!isset($result[$setupId])) { $result[$setupId] = []; }

                $result[$setupId][] = $iblockId;
            }

            foreach ($needFetch as $setupId)
            {
                static::$setupIblockCache[$setupId] = (isset($result[$setupId]) ? $result[$setupId] : []);
            }
        }

        return $result;
    }
}