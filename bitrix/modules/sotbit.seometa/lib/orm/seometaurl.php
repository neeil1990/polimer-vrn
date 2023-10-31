<?php

namespace Sotbit\Seometa\Orm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SiteTable;
use Sotbit\Seometa\Orm\Collections\SeometaUrlCollection;

Loc::loadMessages(__FILE__);

/**
 * Class SeometaUrlTable
 * @package Sotbit\Seometa\Orm
 */
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
     * @return string
     */
    public static function getCollectionClass()
    {
        return SeometaUrlCollection::class;
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            'ID' => [
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_ID_FIELD'),
            ],
            'SORT' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_SORT_FIELD'),
            ],
            'CONDITION_ID' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_CONDITION_ID_FIELD'),
            ],
            'ACTIVE' => [
                'data_type' => 'boolean',
                'values' => [
                    'N',
                    'Y'
                ],
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_ENABLE_FIELD'),
            ],
            'REAL_URL' => [
                'data_type' => 'text',
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_REAL_URL_FIELD'),
            ],
            'NEW_URL' => [
                'data_type' => 'text',
                'title' => Loc::getMessage('SEOMETA_URL_ENTITY_NEW_URL_FIELD'),
            ],
            'CATEGORY_ID' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_CATEGORY_ID'),
            ],
            'NAME' => [
                'data_type' => 'string',
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_NAME'),
            ],
            'PROPERTIES' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_PROPERTIES'),
            ],
            'iblock_id' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_IBLOCK_ID'),
            ],
            'section_id' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_SECTION_ID'),
            ],
            'DATE_CHANGE' => [
                'data_type' => 'datetime',
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_DATE_CHANGE_FIELD'),
                'default_value' => function () {
                    return new  \Bitrix\Main\Type\DateTime();
                }
            ],
            'PRODUCT_COUNT' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_PRODUCT_COUNT_FIELD'),
            ],
            'IN_SITEMAP' => [
                'data_type' => 'boolean',
                'values' => [
                    'N',
                    'Y'
                ],
                'title' => Loc::getMessage('SEOMETA_IN_SITEMAP_FIELD'),
            ],
            'STATUS' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_STATUS_FIELD'),
            ],
            'DESCRIPTION' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_DESCRIPTION_FIELD'),
            ],
            'KEYWORDS' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_KEYWORDS_FIELD'),
            ],
            'TITLE' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_TITLE_FIELD'),
            ],
            'DATE_SCAN' => [
                'data_type' => 'datetime',
                'title' => Loc::getMessage('SEOMETA_DATE_SCAN_FIELD'),
            ],
            'ELEMENT_FILE' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_IMAGE_FIELD'),
            ],
            'SITE_ID' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_SITE_ID')
            ],
            'CHPU_SEODATA' => [
                'data_type' => 'Sotbit\Seometa\Orm\ChpuSeoDataTable',
                'reference' => ['=this.REAL_URL' => 'ref.BITRIX_URL', '=this.SITE_ID' => 'ref.SITE_ID'],
            ],
            'PARENT_CONDITION' => [
                'data_type' => 'Sotbit\Seometa\Orm\ConditionTable',
                'reference' => ['=this.CONDITION_ID' => 'ref.ID']
            ],
        ];
    }

    /**
     * Delete chpu by ID with all dependencies
     *
     * @param $id
     * @return mixed
     */
    public static function delete(
        $id
    )
    {
        $res = self::getList([
            'select' => ['REAL_URL', 'SITE_ID'],
            'filter' => ['ID' => $id],
            'order' => ['ID']
        ])->fetch();

        ChpuLinksTable::deleteByMainChpuId($id);
        //TODO: Need understand, needed whether parameter SITE_ID for resolve dependencies
        //ChpuSeoDataTable::deleteByBitrixUrl($res['REAL_URL'], $res['SITE_ID']);

        return parent::delete($id);
    }

    /**
     * @param false $conditionID
     * @param false $sectionID
     * @param false $mode
     */
    public static function deleteByOptions(
        $conditionID = false,
        $sectionID = false,
        $mode = false
    )
    {
        $filter = [];
        if (!empty($conditionID) && is_numeric($conditionID)) {
            $filter['CONDITION_ID'] = $conditionID;
        }

        if (!empty($sectionID) && is_array($sectionID)) {
            $filter['section_id'] = $sectionID;
        } else if (!empty($sectionID) && is_numeric($sectionID)) {
            $filter['section_id'] = [$sectionID];
        }

        if ($filter) {
            $arr = self::getList([
                'select' => [
                    'ID',
                    'CONDITION_ID'
                ],
                'filter' => $filter,
                'order' => ['ID']
            ]);

            while ($item = $arr->fetch()) {
                if ($mode == 'all') {
                    //TODO: Need understand, needed whether parameter SITE_ID for resolve dependencies
                    ChpuLinksTable::deleteByMainChpuId($item['ID']);
                    ChpuSeoDataTable::deleteByConditionId($item['CONDITION_ID']);
                }

                parent::delete($item['ID']);
            }
        }
    }

    /**
     * @param $id
     * @return array
     */
    public static function getByCondition(
        $id
    )
    {
        $res = self::getList([
            'select' => [
                'ID',
                'SORT',
                'REAL_URL',
                'NEW_URL',
                'DATE_CHANGE',
                'NAME',
                'SITE_ID'
            ],
            'filter' => ['CONDITION_ID' => $id],
            'order' => ['ID'],
        ]);
        $resAll = [];
        while ($one = $res->fetch()) {
            $resAll[$one['ID']] = $one;
        }

        return $resAll;
    }

    /**
     * @param array $arr
     * @return \Bitrix\Main\Entity\AddResult|false
     */
    public static function add(
        array $arr = []
    )
    {
        $isExist = !boolval(self::getList([
            'filter' => [
                '=REAL_URL' => $arr['REAL_URL'],
                'SITE_ID' => $arr['SITE_ID']
            ],
            'group' => []
        ])->fetch());

        if ($isExist) {
            return parent::add($arr);
        }

        return false;
    }

    public static function addAfterParse(?array $arr = [])
    {
        $isExist = !boolval(self::getList([
            'filter' => [
                '=REAL_URL' => $arr['REAL_URL'],
                'SITE_ID' => $arr['SITE_ID']
            ],
            'group' => []
        ])->fetch());

        $defSite = SiteTable::query()
            ->addSelect('*')
            ->addFilter('DEF','Y')
            ->fetchAll();

        if ($isExist) {
            $chpu = [
                'ACTIVE' => $arr['ACTIVE'] ?: 'N',
                'NAME' => $arr['NAME'],
                'SORT' => $arr['SORT'] ?: 100,
                'REAL_URL' => $arr['REAL_URL'],
                'NEW_URL' => $arr['NEW_URL'],
                'SITE_ID' => $arr['SITE_ID'] ?: $defSite[0]['LID'],
                'CATEGORY_ID' => $arr['CATEGORY_ID'] ?: 0,
            ];
            $resultCHPU = parent::add($chpu);
            if (!$resultCHPU->isSuccess()) {
                $result = '<span style="color: red">' .  $resultCHPU->getErrorMessages()[0] . '</span>';
            } else {
                foreach ($arr as $key => $value) {
                    if (stripos($key, 'ELEMENT_') !== false) {
                        if ($key === 'ELEMENT_FILE') {
                            $arrFile = \CFile::MakeFileArray($value);
                            $seoMetaData[$key] = \CFile::SaveFile($arrFile, 'seo_images');
                        } else {
                            $seoMetaData[$key] = $value;
                        }
                        $seoMetaData[$key . '_REPLACE'] = 'Y';
                    }
                }

                $chpuSeoData = [
                    'BITRIX_URL' => $arr['REAL_URL'],
                    'SEOMETA_DATA' => serialize($seoMetaData),
                    'SITE_ID' => $arr['SITE_ID']
                ];
                $resultCHPUSeo = ChpuSeoDataTable::add($chpuSeoData);
                if (!$resultCHPUSeo->isSuccess()) {
                    $result = '<span style="color: red">' . $resultCHPU->getErrorMessages()[0] . '</span>';
                } else {
                    $result = null;
                }
            }

            return $result;
        }
        return '<span style="color: red">' . Loc::getMessage('SEO_META_CHPU_EXIST') . '</span>';
    }

    /**
     * @param $id
     * @param array $arr
     * @return \Bitrix\Main\Entity\UpdateResult|false
     */
    public static function update(
        $id = '',
        array $arr = []
    )
    {
        $isExist = !boolval(self::getList([
            'filter' => [
                '!=ID' => $id,
                '=REAL_URL' => $arr['REAL_URL'],
                'SITE_ID' => $arr['SITE_ID']
            ],
            'group' => []
        ])->fetch());

        if ($isExist) {
            return parent::update($id, $arr);
        }

        return false;
    }

    /**
     * @param $url
     * @param $siteId
     * @return bool
     */
    public static function isUrlExist(
        $url,
        $siteId
    )
    {
        return (self::getByRealUrl($url, $siteId) || self::getByNewUrl($url, $siteId));
    }

    /**
     * @param $url
     * @param $siteId
     * @return array|false
     */
    public static function getByRealUrl(
        $url,
        $siteId
    )
    {
        $filter = [
            'ACTIVE' => 'Y',
            '=REAL_URL' => $url,
            'SITE_ID' => $siteId
        ];
        $result = self::getList([
            'select' => ['*'],
            'filter' => $filter,
            'order' => ['ID'],
            'limit' => 1
        ])->fetch();

        if ($result) {
            //TODO: Need understand, needed whether parameter SITE_ID for resolve dependencies
            $result['CHPU_LINKS'] = ChpuLinksTable::getByMainChpuId($result['ID']);
            $result['SEOMETA_DATA'] = ChpuSeoDataTable::getByBitrixUrl($url, $result['SITE_ID']);
        }

        return $result;
    }

    /**
     * @param $url
     * @param $siteId
     * @return array|false
     */
    public static function getByRealUrlAndCond(
        $url,
        $siteId,
        $condId
    )
    {
        $filter = [
            'ACTIVE' => 'Y',
            '=REAL_URL' => $url,
            'CONDITION_ID' => $condId,
            'SITE_ID' => $siteId
        ];
        $result = self::getList([
            'select' => ['*'],
            'filter' => $filter,
            'order' => ['ID'],
            'limit' => 1
        ])->fetch();

        if ($result) {
            //TODO: Need understand, needed whether parameter SITE_ID for resolve dependencies
            $result['CHPU_LINKS'] = ChpuLinksTable::getByMainChpuId($result['ID']);
            $result['SEOMETA_DATA'] = ChpuSeoDataTable::getByBitrixUrl($url, $result['SITE_ID']);
        }

        return $result;
    }

    /**
     * @param $url
     * @param $siteID
     * @return array|false
     */
    public static function getByNewUrl(
        $url,
        $siteID
    )
    {
        $filter = [
            'ACTIVE' => 'Y',
            '=NEW_URL' => $url,
            'SITE_ID' => $siteID
        ];

        $result = self::getList([
            'select' => ['*'],
            'filter' => $filter,
            'order' => ['ID'],
            'limit' => 1
        ])->fetch();

        if ($result) {
            //TODO: Need understand, needed whether parameter SITE_ID for resolve dependencies
            $result['CHPU_LINKS'] = ChpuLinksTable::getByMainChpuId($result['ID']);
            $result['SEOMETA_DATA'] = ChpuSeoDataTable::getByBitrixUrl($result['REAL_URL'], $result['SITE_ID']);
        }

        return $result;
    }

    /**
     * @param string $siteId
     * @return array
     */
    public static function getAll(
        $siteId = ''
    )
    {
        $filter = [
            ['ACTIVE' => 'Y']
        ];

        if ($siteId) {
            $filter['SITE_ID'] = $siteId;
        }

        $res = self::getList([
            'select' => [
                'ID',
                'SORT',
                'REAL_URL',
                'NEW_URL',
                'DATE_CHANGE',
                'CONDITION_ID'
            ],
            'filter' => $filter,
            'order' => ['ID'],
        ]);

        $resAll = [];
        while ($one = $res->fetch()) {
            $resAll[$one['ID']] = [
                'REAL_URL' => $one['REAL_URL'],
                'NEW_URL' => $one['NEW_URL'],
                'DATE_CHANGE' => $one['DATE_CHANGE'],
                'CONDITION_ID' => $one['CONDITION_ID']
            ];
        }

        return $resAll;
    }

    /**
     * @param $id
     * @return array
     */
    public static function getAllByCondition(
        $id
    )
    {
        $seoData = ChpuSeoDataTable::getByConditionId($id);
        $arrChpuID = array_column($seoData, 'CHPU_ID', 'ID');
        $res = self::getList([
            'select' => [
                'ID',
                'SORT',
                'REAL_URL',
                'NEW_URL',
                'NAME',
                'CONDITION_ID',
                'PROPERTIES',
                'section_id',
                'ACTIVE',
                'PRODUCT_COUNT',
                'SITE_ID'
            ],
            'filter' => ['CONDITION_ID' => $id],
            'order' => ['ID'],
        ]);
        $resAll = [];
        while ($one = $res->fetch()) {
            if (($index = array_search($one['ID'], $arrChpuID)) !== false) {
                $seoData[$index]['SEO_DATA'] = unserialize($seoData[$index]['SEO_DATA']);
                $one['SEOMETA_DATA'] = $seoData[$index];
            }

            $resAll[$one['ID']] = $one;
        }

        return $resAll;
    }

    /**
     * @param string $id
     * @return array|\CDBResult|false
     */
    public static function getById(
        $id = ""
    )
    {
        $result = parent::getById($id)->fetch();
        $seoData = ChpuSeoDataTable::getByBitrixUrl($result['REAL_URL'], $result['SITE_ID']);
        if ($seoData) {
            $result['SEOMETA_DATA'] = $seoData;
        }

        return $result;
    }

    /**
     * @param array $arrId
     * @return array
     */
    public static function getByArrId(
        array $arrId
    )
    {
        $filter = [
            'ID' => $arrId,
            'ACTIVE' => 'Y'
        ];

        $select = [
            'ID',
            'SORT',
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
        while ($item = $res->fetch()) {
            $result[$item['ID']] = $item;
        }

        return $result;
    }

    /**
     * @param $id
     * @return string
     */
    public static function getArrIdsByConditionId(
        $id
    )
    {
        $res = self::getList([
            'select' => ['ID'],
            'filter' => ['CONDITION_ID' => $id],
            'order' => ['ID'],
        ]);

        $result = [];
        while ($one = $res->fetch()) {
            $result[] = $one['ID'];
        }

        $result = implode(',', $result);

        return $result;
    }

    // for scaner

    /**
     * @param $lastID
     * @param $limit
     * @return \Bitrix\Main\ORM\Query\Result
     */
    public static function getPartOfURLs(
        $lastID,
        $limit
    )
    {
        $res = self::getList([
            'select' => ['ID', 'REAL_URL', 'NEW_URL'],
            'filter' => ['>ID' => $lastID],
            'order' => ['ID'],
            'limit' => $limit
        ]);

        return $res;
    }

    // for reindex

    public static function checkExistChpuReindex($newUrl, $conditionId, $siteId)
    {
        $res = boolval(self::getList([
            'select' => ['ID'],
            'filter' => [
                'NEW_URL' => $newUrl,
                'CONDITION_ID' => $conditionId,
                'SITE_ID' => $siteId
            ]
        ])->fetch());

        if ($res) {
            return true;
        }
        return false;
    }
}