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
 * <li> BITRIX_URL int mandatory
 * <li> CONDITION_ID int
 * <li> SEOMETA_DATA string
 * </ul>
 *
 * @package Bitrix\Sotbit
 **/

class ChpuSeoDataTable extends \DataManagerEx_SeoMeta
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_sotbit_seometa_chpu_seo_data';
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
                'title' => Loc::getMessage('SEOMETA_CHPU_SEO_DATA_ID'),
            ),
            'BITRIX_URL' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_CHPU_SEO_DATA_BITRIX_URL'),
            ),
            'CONDITION_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_CHPU_SEO_DATA_CONDITION_ID'),
            ),
            'SEOMETA_DATA' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_CHPU_SEO_DATA_SEO_DATA'),
            )
        );
    }

    public static function deleteById($id){
        $arr = self::getList(array(
                'select' => array('ID', 'SEOMETA_DATA'),
                'filter' => array('ID' => $id),
                'order'  => array('ID'), )
        );

        while($one = $arr->fetch()){
            if($data = unserialize($one['SEOMETA_DATA'])) {
                \CFile::Delete($data['ELEMENT_FILE']);
            }
            self::delete($one['ID']);
        }
    }

    public static function deleteByBitrixUrl($url){
        $arr = self::getList(array(
                'select' => array('ID', 'SEOMETA_DATA'),
                'filter' => array('BITRIX_URL' => $url),
                'order'  => array('ID'), )
        );

        while($one = $arr->fetch()){
            if($data = unserialize($one['SEOMETA_DATA'])) {
                \CFile::Delete($data['ELEMENT_FILE']);
            }
            self::delete($one['ID']);
        }
    }

    public static function deleteByConditionId($id){
        $arr = self::getList(array(
            'select' => array('ID', 'SEOMETA_DATA'),
            'filter' => array('CONDITION_ID' => $id),
            'order'  => array('ID'), ));

        while($one = $arr->fetch()){
            if($data = unserialize($one['SEOMETA_DATA'])) {
                \CFile::Delete($data['ELEMENT_FILE']);
            }

            self::delete($one['ID']);
        }
    }

    public static function getByConditionId($conditionId){
        $res = self::getList(array(
            'select' => array('*'),
            'filter' => array(
                'CONDITION_ID' => $conditionId
            ),
            'order'  => array('ID'),
        ));
        $resAll = array();

        while($one = $res->fetch())
        {
            if($data = unserialize($one['SEOMETA_DATA'])) {
                $one = array_merge($one, $data);
            }
            $resAll[$one['ID']] = $one;
        }
        return $resAll;
    }

    public static function getByBitrixUrl($bitrixUrl){
        $res = self::getList(array(
            'select' => array('*'),
            'filter' => array(
                'BITRIX_URL' => $bitrixUrl
            ),
            'order'  => array('ID'),
        ));
        $one = $res->fetch();

        if($data = unserialize($one['SEOMETA_DATA'])) {
            $one = array_merge($one, $data);
        }

        return $one;
    }

    public static function getAll(){
        $res = self::getList(array(
            'select' => array('*'),
            'order'  => array('ID'),
        ));
        $resAll = array();

        while($one = $res->fetch())
        {
            if($data = unserialize($one['SEOMETA_DATA'])) {
                $one = array_merge($one, $data);
            }

            $resAll[$one['ID']] = $one;
        }
        return $resAll;
    }
}