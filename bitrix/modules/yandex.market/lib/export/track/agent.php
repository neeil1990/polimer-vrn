<?php

namespace Yandex\Market\Export\Track;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Agent extends Market\Reference\Agent\Base
{
    /**
     * Регистрация изменения сущности
     *
     * @param $entityType string
     * @param $entityId int
     *
     * @return bool
     */
    public static function entityChange($entityType, $entityId)
    {
        global $pPERIOD;

        $isNeedRepeat = false;

        Manager::registerEntityChange($entityType, $entityId);

        $model = static::getEntityModel($entityType, $entityId);

        if ($model !== null)
        {
            $nextDate = static::getNextChangeDate($entityType, $model);

            static::updateListener($entityType, $model);

            if ($nextDate !== null)
            {
                $isNeedRepeat = true;
                $pPERIOD = $nextDate->getTimestamp() - time();
            }
        }

        return $isNeedRepeat;
    }

    /**
     * Модель сущности
     *
     * @param $entityType
     * @param $entityId
     *
     * @return Market\Reference\Storage\Model|null
     */
    protected static function getEntityModel($entityType, $entityId)
    {
        $result = null;

        try
        {
            switch ($entityType)
            {
                case Table::ENTITY_TYPE_PROMO:
                    $result = Market\Export\Promo\Model::loadById($entityId);
                break;
            }
        }
        catch (Main\ObjectNotFoundException $exception)
        {
            $result = null;
        }

        return $result;
    }

    /**
     * Дата следующего изменения
     *
     * @param $entityType
     * @param $model
     *
     * @return Main\Type\Date|null
     */
    protected static function getNextChangeDate($entityType, $model)
    {
        $result = null;

        try
        {
            switch ($entityType)
            {
                case Table::ENTITY_TYPE_PROMO:
                    /** @var Market\Export\Promo\Model $model */
                    $result = $model->getNextActiveDate();
                break;
            }
        }
        catch (Main\SystemException $exception)
        {
            $result = null;
        }

        return $result;
    }

    /**
     * Обновление обработчиков для сущности
     *
     * @param $entityType string
     * @param $model Market\Reference\Storage\Model
     */
    protected static function updateListener($entityType, $model)
    {
        try
        {
            switch ($entityType)
            {
                case Table::ENTITY_TYPE_PROMO:
                    /** @var Market\Export\Promo\Model $model */
                    $model->handleChanges();
                break;
            }
        }
        catch (Main\SystemException $exception)
        {
            // nothing
        }
    }
}