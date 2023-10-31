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
            'ELEMENT_FILE' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_IMAGE_FIELD'),
            ),
            'SITE_ID' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_SITE_ID')
            )
        );
    }

    public static function delete($id){
        $res = self::getList(array(
                'select' => array('REAL_URL'),
                'filter' => array('ID' => $id),
                'order'  => array('ID')
            ))->fetch();

        ChpuLinksTable::deleteByMainChpuId($id);
        ChpuSeoDataTable::deleteByBitrixUrl($res['REAL_URL']);
        return parent::delete($id);
    }

    public static function deleteByOptions($conditionID = false, $sectionID = false, $mode = false) {
        $filter = [];
        if ($conditionID && $conditionID == intval($conditionID)) {
            $filter['CONDITION_ID'] = $conditionID;
        }

        if($sectionID) {
            if(is_array($sectionID)) {
                $filter['section_id'] = $sectionID;
            } else if($sectionID == intval($sectionID)) {
                $filter['section_id'] = [$sectionID];
            }
        }

        if($filter) {
            $arr = self::getList(array(
                'select' => array('ID', 'CONDITION_ID'),
                'filter' => $filter,
                'order'  => array('ID')
            ));

            while($item = $arr->fetch()){
                if($mode == 'all') {
                    ChpuLinksTable::deleteByMainChpuId($item['ID']);
                    ChpuSeoDataTable::deleteByConditionId($item['CONDITION_ID']);
                }

                parent::delete($item['ID']);
            }
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

    public static function add(
        array $arr = array()
    ) {
        if(!boolval(self::getList([
            'filter' => ['=REAL_URL' => $arr['REAL_URL']],
            'group' => []
        ])->fetch())) {
            return parent::add($arr);
        }

        return false;
    }

    public static function getByRealUrl($url){
        $filter = array('ACTIVE' => 'Y', '=REAL_URL' => $url);
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        if(SITE_ID && $request && !$request->isAdminSection()) {
            $filter['SITE_ID'] = '%' . SITE_ID . '%';
        }

        $result = self::getList(array(
            'select' => array('*'),
            'filter' => $filter,
            'order'  => array('ID'),
            'limit'  => 1
        ))->fetch();

        if($result) {
            $result['CHPU_LINKS'] = ChpuLinksTable::getByMainChpuId($result['ID']);
            $result['SEOMETA_DATA'] = ChpuSeoDataTable::getByBitrixUrl($url);
        }

        return $result;
    }

    public static function getByNewUrl($url){
        $filter = array('ACTIVE' => 'Y', '=NEW_URL' => $url);
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        if(SITE_ID && $request && !$request->isAdminSection()) {
            $filter['SITE_ID'] = '%' . SITE_ID . '%';
        }

        $result = self::getList(array(
            'select' => array('*'),
            'filter' => $filter,
            'order'  => array('ID'),
            'limit'  => 1
        ))->fetch();

        if($result) {
            $result['CHPU_LINKS'] = ChpuLinksTable::getByMainChpuId($result['ID']);
            $result['SEOMETA_DATA'] = ChpuSeoDataTable::getByBitrixUrl($result['REAL_URL']);
        }

        return $result;
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
        $seoData = ChpuSeoDataTable::getByConditionId($id);

        $arrBitrixUrl = array_column($seoData, 'BITRIX_URL', 'ID');

        $res = self::getList(array(
            'select' => array('ID', 'REAL_URL', 'NEW_URL', 'NAME', 'CONDITION_ID', 'PROPERTIES', 'section_id', 'ACTIVE', 'PRODUCT_COUNT'),
            'filter' => array('CONDITION_ID' => $id),
            'order'  => array('ID'),
        ));
        $resAll = array();
        while($one = $res->fetch())
        {
            if(($index = array_search($one['REAL_URL'], $arrBitrixUrl)) !== false) {
                $seoData[$index]['SEO_DATA'] = unserialize($seoData[$index]['SEO_DATA']);
                $one['SEOMETA_DATA'] = $seoData[$index];
            }
            $resAll[$one['ID']] = $one;
        }
        return $resAll;
    }

    public static function getById($id = "")
    {
        $result = parent::getById($id)->fetch();

        $seoData = ChpuSeoDataTable::getByBitrixUrl($result['REAL_URL']);
        $result['SEOMETA_DATA'] = $seoData;

//        $bottomTags = ChpuTagsTable::getByChpuID($id);
//
//        $result['TAG_OVERRIDE_TYPE'] = $bottomTags['TAG_OVERRIDE_TYPE'];
//        $result['TAG_DATA'] = $bottomTags['TAG_DATA'];

        return $result;
    }

    public static function getByArrId(array $arrId)
    {
        $filter = [
            'ID' => $arrId,
            'ACTIVE' => 'Y'
        ];

        $select = [
            'ID',
            'REAL_URL',
            'NEW_URL',
            'NAME',
            'CONDITION_ID',
            'PROPERTIES',
            'ACTIVE'
        ];

        $res = parent::getList([
            'filter' => $filter,
            'select' => $select
        ]);
        $result = [];
        while($item = $res->fetch()) {
            $result[$item['ID']] = $item;
        }

        return $result;
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