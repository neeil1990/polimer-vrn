<?php
namespace Sotbit\Seometa\Orm;

use Bitrix\Main\Localization\Loc;

/**
 * Class ChpuTagsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CHPU_ID int mandatory
 * <li> PROPERTIES string
 * </ul>
 *
 * @package Bitrix\Sotbit
 **/

class ChpuTagsTable extends \DataManagerEx_SeoMeta
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_sotbit_seometa_chpu_bottom_tag';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('SEOMETA_TAGS_ID_FIELD'),
            ),
            'CHPU_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_TAGS_CHPU_ID_FIELD'),
            ),
            'TAG_OVERRIDE_TYPE' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_TAGS_PROPERTIES_FIELD'),
            ),
            'TAG_DATA' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_TAGS_PROPERTIES_FIELD'),
            )
        );
    }

    /**
     * Get chpu tags by chpu ID
     *
     * @param $id
     * @return array|false
     */
    public static function getByChpuID($id)
    {
        $result = self::getList(array(
            'filter' => array('CHPU_ID' => $id)
        ))->fetch();

        if(isset($result['TAG_DATA'])) {
            $result['TAG_DATA'] = unserialize($result['TAG_DATA']);
        }

        return $result;
    }
}