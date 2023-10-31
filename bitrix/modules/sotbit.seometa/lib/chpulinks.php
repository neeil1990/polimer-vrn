<?php
namespace Sotbit\Seometa;

use Bitrix\Main\Entity;
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
    public static function getTableName()
    {
        return 'b_sotbit_seometa_chpu_links';
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
                'title' => Loc::getMessage('SEOMETA_LINKS_ID_FIELD'),
            ),
            'MAIN_CHPU_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_LINKS_MAIN_CHPU_ID'),
            ),
            'LINK_CHPU_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_LINKS_LINK_CHPU_ID'),
            ),
            'SEOMETA_DATA_CHPU_LINK' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_LINKS_SEOMETA_DATA'),
            )
        );
    }

    public static function delete($id){
        $arr = self::getList(array(
            'select' => array('ID', 'SEOMETA_DATA_CHPU_LINK'),
            'filter' => array('ID' => $id),
            'order'  => array('ID'), ));

        while($one = $arr->fetch()){
            if($one['SEOMETA_DATA_CHPU_LINK'] = unserialize($one['SEOMETA_DATA_CHPU_LINK'])) {
                \CFile::Delete($one['SEOMETA_DATA_CHPU_LINK']['ELEMENT_FILE']);
            }
            parent::delete($one['ID']);
        }
    }

    public static function deleteByMainChpuId($id){
        $arr = self::getList(array(
            'select' => array('ID', 'SEOMETA_DATA_CHPU_LINK'),
            'filter' => array('MAIN_CHPU_ID' => $id),
            'order'  => array('ID'), ));

        while($one = $arr->fetch()){
            if($one['SEOMETA_DATA_CHPU_LINK'] = unserialize($one['SEOMETA_DATA_CHPU_LINK'])) {
                \CFile::Delete($one['SEOMETA_DATA_CHPU_LINK']['ELEMENT_FILE']);
            }

            parent::delete($one['ID']);
        }
    }

    public static function getByMainChpuId($mainChpuId){
        $res = self::getList(array(
            'select' => array('*'),
            'filter' => array(
                'MAIN_CHPU_ID' => $mainChpuId
            ),
            'order'  => array('ID'),
        ));
        $resAll = array();

        while($one = $res->fetch())
        {
            $one['SEOMETA_DATA_CHPU_LINK'] = unserialize($one['SEOMETA_DATA_CHPU_LINK']);
            $resAll[$one['ID']] = $one;
        }

        return $resAll;
    }

    public static function getAll(){
        $res = self::getList(array(
            'select' => array('*'),
            'order'  => array('ID'),
        ));
        $resAll = array();

        while($one = $res->fetch())
        {
            $one['SEOMETA_DATA_CHPU_LINK'] = unserialize($one['SEOMETA_DATA_CHPU_LINK']);
            $resAll[$one['ID']] = $one;
        }
        return $resAll;
    }

    public static function checkExist($mainChpuId, $linkChpuId){
        $res = self::getList(array(
            'select' => array('ID'),
            'filter' => array(
                'MAIN_CHPU_ID' => $mainChpuId,
                'LINK_CHPU_ID' => $linkChpuId
            ),
            'order'  => array('ID'),
        ));

        return !empty($res->fetch());
    }
}