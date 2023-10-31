<?php
namespace Sotbit\Seometa\Orm;

use Bitrix\Main\Localization\Loc;

/**
 * Class SeometaLinksTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> MAIN_CHPU_ID int mandatory
 * <li> LINK_CHPU_ID int mandatory
 * <li> SEOMETA_DATA_CHPU_LINK string
 * </ul>
 *
 * @package Bitrix\Sotbit
 **/

class ChpuLinksTable extends \DataManagerEx_SeoMeta
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName(
    ) {
        return 'b_sotbit_seometa_chpu_links';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap(
    ) {
        return [
            'ID' => [
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('SEOMETA_LINKS_ID_FIELD'),
            ],
            'MAIN_CHPU_ID' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_LINKS_MAIN_CHPU_ID'),
            ],
            'LINK_CHPU_ID' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_LINKS_LINK_CHPU_ID'),
            ],
            'SEOMETA_DATA_CHPU_LINK' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_LINKS_SEOMETA_DATA'),
            ]
        ];
    }

    /**
     * Delete link by ID
     *
     * @param $id
     */
    public static function delete(
        $id
    ) {
        $arr = self::getList([
            'select' => [
                'ID',
                'SEOMETA_DATA_CHPU_LINK'
            ],
            'filter' => ['ID' => $id],
            'order' => ['ID'],
        ]);

        while($one = $arr->fetch()){
            if($one['SEOMETA_DATA_CHPU_LINK'] = unserialize($one['SEOMETA_DATA_CHPU_LINK'])) {
                \CFile::Delete($one['SEOMETA_DATA_CHPU_LINK']['ELEMENT_FILE']);
            }

            parent::delete($one['ID']);
        }
    }

    /**
     * Delete link by main chpu ID
     *
     * @param $id
     */
    public static function deleteByMainChpuId(
        $id
    ) {
        $arr = self::getList([
            'select' => [
                'ID',
                'SEOMETA_DATA_CHPU_LINK'
            ],
            'filter' => [
                "LOGIC"=>"OR",
                ['MAIN_CHPU_ID' => $id],
                ['LINK_CHPU_ID' => $id]
            ],
            'order' => ['ID'],
        ]);

        while($one = $arr->fetch()){
            if($one['SEOMETA_DATA_CHPU_LINK'] = unserialize($one['SEOMETA_DATA_CHPU_LINK'])) {
                \CFile::Delete($one['SEOMETA_DATA_CHPU_LINK']['ELEMENT_FILE']);
            }

            parent::delete($one['ID']);
        }
    }

    /**
     * Get link by main chpu ID
     *
     * @param $mainChpuId
     * @return array
     */
    public static function getByMainChpuId(
        $mainChpuId
    ) {
        $res = self::getList([
            'select' => ['*'],
            'filter' => [
                'MAIN_CHPU_ID' => $mainChpuId
            ],
            'order' => ['ID'],
        ]);

        $resAll = [];

        while ($one = $res->fetch()) {
            $one['SEOMETA_DATA_CHPU_LINK'] = unserialize($one['SEOMETA_DATA_CHPU_LINK']);
            $resAll[$one['ID']] = $one;
        }

        return $resAll;
    }

    /**
     * Get all links
     *
     * @return array
     */
    public static function getAll(
    ) {
        $res = self::getList([
            'select' => ['*'],
            'order' => ['ID'],
        ]);

        $resAll = [];

        while ($one = $res->fetch()) {
            $one['SEOMETA_DATA_CHPU_LINK'] = unserialize($one['SEOMETA_DATA_CHPU_LINK']);
            $resAll[$one['ID']] = $one;
        }

        return $resAll;
    }

    /**
     * Check for exist link by main chpu ID and link chpu ID
     *
     * @param $mainChpuId
     * @param $linkChpuId
     * @return bool
     */
    public static function checkExist(
        $mainChpuId,
        $linkChpuId
    ) {
        $res = self::getList([
            'select' => ['ID'],
            'filter' => [
                'MAIN_CHPU_ID' => $mainChpuId,
                'LINK_CHPU_ID' => $linkChpuId
            ],
            'order' => ['ID'],
        ]);

        return !empty($res->fetch());
    }
}