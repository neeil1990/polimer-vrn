<?php
namespace Sotbit\Seometa;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class SeometaUrlTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ID_CONDITION int mandatory
 * <li> ENABLE bool optional default 'Y'
 * <li> REAL_URL string mandatory
 * <li> NEW_URL string mandatory
 * </ul>
 *
 * @package Bitrix\Sotbit
 **/

class SeometaUrlTable extends \DataManagerEx_SeoMeta
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_sotbit_seometa_chpu';
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
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_ID_FIELD'),
            ),
            'CONDITION_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_CONDITION_ID_FIELD'),
            ),
            'ACTIVE' => array(
                'data_type' => 'boolean',
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_ENABLE_FIELD'),
            ),
            'REAL_URL' => array(
                'data_type' => 'text',
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_REAL_URL_FIELD'),
            ),
            'NEW_URL' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_NEW_URL_FIELD'),
            ),
            'CATEGORY_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_CATEGORY_ID'),
            ),
            'NAME' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_NAME'),
            ),
            'PROPERTIES' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_PROPERTIES'),
            ),
            'iblock_id' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_IBLOCK_ID'),
            ),
            'section_id' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_SECTION_ID'),
            ),
            'DATE_CHANGE' => array(
                'data_type' => 'datetime',
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_DATE_CHANGE_FIELD'),
            ),
            'PRODUCT_COUNT' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_PRODUCT_COUNT_FIELD'),
            ),
            'IN_SITEMAP' => array(
				'data_type' => 'boolean',
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('SEOMETA_IN_SITEMAP_FIELD'),
            ),
            'STATUS' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('SEOMETA_STATUS_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('SEOMETA_DESCRIPTION_FIELD'),
			),
			'KEYWORDS' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('SEOMETA_KEYWORDS_FIELD'),
            ),
            'TITLE' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('SEOMETA_TITLE_FIELD'),
            ),
            'DATE_SCAN' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('SEOMETA_DATE_SCAN_FIELD'),
            ),
        );
    }
    
    public static function deleteByConditionId($id){
        $arr = self::getList(array(
            'select' => array('ID'),
            'filter' => array('CONDITION_ID' => $id),
            'order'  => array('ID'), ));
            while($one = $arr->fetch()){
                self::delete($one['ID']);
            }
    }
    
    public static function deleteBySectionId($id){
        $arr = self::getList(array(
            'select' => array('ID'),
            'filter' => array('section_id' => $id),
            'order'  => array('ID'), ));
            while($one = $arr->fetch()){
                self::delete($one['ID']);
            }
    }

    public static function getByCondition($id){
       $res = self::getList(array(
            'select' => array('ID', 'REAL_URL', 'NEW_URL', 'DATE_CHANGE', 'NAME'),
            'filter' => array('CONDITION_ID' => $id),
            'order'  => array('ID'),
        ));            
        $resAll = array();
        while($one = $res->fetch()){     
            $resAll[$one['ID']] = $one;
        }
        return $resAll; 
    }
    
    public static function getByRealUrl($url){
        $res = self::getList(array(
            'select' => array('*'),
            'filter' => array('ACTIVE' => 'Y', '=REAL_URL' => $url),
            'order'  => array('ID'),
            'limit'  => 1
        ));
        return $res->fetch();
    }
    
    public static function getByRealUrlGenerate($url){
        $res = self::getList(array(
            'select' => array('*'),
            'filter' => array('=REAL_URL' => $url),
            'order'  => array('ID'),
            'limit'  => 1
        ));
        return $res->fetch();
    }
    
    public static function getByNewUrl($url){
        $res = self::getList(array(
            'select' => array('*'),
            'filter' => array('ACTIVE' => 'Y', '=NEW_URL' => $url),
            'order'  => array('ID'),
            'limit'  => 1
        ));
        return $res->fetch();
    }
    
    public static function getAll(){
        $res = self::getList(array(
            'select' => array('ID', 'REAL_URL', 'NEW_URL', 'DATE_CHANGE', 'CONDITION_ID'),
            'filter' => array('ACTIVE' => 'Y'),
            'order'  => array('ID'),      
        ));            
        $resAll = array();
        while($one = $res->fetch())
        {
            $resAll[$one['ID']] = array(
                'REAL_URL' => $one['REAL_URL'],
                'NEW_URL' => $one['NEW_URL'],
                'DATE_CHANGE' => $one['DATE_CHANGE'],
                'CONDITION_ID' => $one['CONDITION_ID']
            );
        }
        return $resAll;
    }

    public static function getAllByCondition($id)
    {
        $res = self::getList(array(
            'select' => array('ID', 'REAL_URL', 'NEW_URL'),
            'filter' => array('CONDITION_ID' => $id),
            'order'  => array('ID'),
        ));
        $resAll = array();
        while($one = $res->fetch())
        {
            $resAll[$one['ID']] = $one;
        }
        return $resAll;
    }

    public static function getArrIdsByConditionId($id)
    {
        $res = self::getList(array(
            'select' => array('ID'),
            'filter' => array('CONDITION_ID' => $id),
            'order'  => array('ID'),
        ));
        $result = array();
        while($one = $res->fetch())
        {
            $result[] = $one['ID'];
        }
        $result = implode(',', $result);
        return $result;
    }



    // for scaner
    public static function getPartOfURLs($lastID, $limit)
    {
        $res = self::getList(array(
            'select' => array('ID', 'REAL_URL', 'NEW_URL'),
            'filter' => array('>ID' => $lastID),
            'order' => array('ID'),
            'limit' => $limit
        ));

        return $res;
    }
}