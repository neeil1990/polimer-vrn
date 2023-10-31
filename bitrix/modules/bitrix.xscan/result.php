<?php

use Bitrix\Main\ORM\Query\Query;

if (!class_exists('\Bitrix\Main\Entity\DataManager')) {
    return;
}

class XScanResultTable extends \Bitrix\Main\Entity\DataManager
{
    public static function getTableName()
    {
        return 'b_xscan_results';
    }

    public static function getMap()
    {
        return array(
            new \Bitrix\Main\Entity\IntegerField('id', array('primary' => true, 'autocomplete' => true)),
            new \Bitrix\Main\Entity\EnumField('type', array(
                'values' => array('file', 'agent', 'event'),
                'default_value' => 'file'
            )),
            new \Bitrix\Main\Entity\StringField('src'),
            new \Bitrix\Main\Entity\StringField('message'),
            new \Bitrix\Main\Entity\FloatField('score'),
            new \Bitrix\Main\Entity\DatetimeField('ctime'),
            new \Bitrix\Main\Entity\DatetimeField('mtime'),
            new \Bitrix\Main\Entity\StringField('tags')
        );
    }

    public static function getCollectionClass()
    {
        return XScanResults::class;
    }

    public static function getObjectClass()
    {
        return XScanResult::class;
    }

    public static function deleteList(array $filter)
    {
        $entity = static::getEntity();
        $connection = $entity->getConnection();

        $where = Query::buildFilterSql($entity, $filter);
        $where = $where ? 'WHERE ' . $where : '';

        $sql = sprintf(
            'DELETE FROM %s %s',
            $connection->getSqlHelper()->quote($entity->getDbTableName()),
            $where
        );

        $res = $connection->query($sql);

        return $res;
    }

}


class XScanResults extends EO_XScanResult_Collection
{
}

class XScanResult extends EO_XScanResult
{
}