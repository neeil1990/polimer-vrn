<?php
namespace Sotbit\Seometa\Orm;

use Bitrix\Main, \Bitrix\Main\Config\Option, \Bitrix\Sale\Order, Bitrix\Main\Localization\Loc, Bitrix\Main\Text\Encoding;
Loc::loadMessages(__FILE__);

/**
 * Class SeometaStatisticsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATE_CREATE datetime optional
 * <li> SORT int optional default 500
 * <li> URL_FROM string optional
 * <li> URL_TO string optional
 * <li> KEYWORDS string optional
 * <li> PAGES_COUNT int optional
 * </ul>
 *
 * @package Bitrix\Sotbit
 *         
 */
class SeometaStatisticsTable extends \DataManagerEx_SeoMeta
{

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_sotbit_seometa_statistics';
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
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_ID_FIELD')
            ],
            'DATE_CREATE' => [
                'data_type' => 'datetime',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_DATE_CREATE_FIELD')
            ],
            'SORT' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_SORT_FIELD')
            ],
            'URL' => [
                'data_type' => 'text',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_URL_FIELD')
            ],
            'NO_INDEX' => [
                'data_type' => 'boolean',
                'values' => [
                    'N',
                    'Y'
                ],
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_NO_INDEX_FIELD')
            ],
            'IN_SITEMAP' => [
                'data_type' => 'boolean',
                'values' => [
                    'N',
                    'Y'
                ],
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_IN_SITEMAP_FIELD'),
            ],
            'CONDITION_ID' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_CONDITION_ID_FIELD')
            ],
            'PAGE_STATUS' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_PAGE_STATUS_FIELD')
            ],
            'META_TITLE' => [
                'data_type' => 'text',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_SEOMETA_INFO_FIELD')
            ],
            'META_KEYWORDS' => [
                'data_type' => 'text',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_SEOMETA_INFO_FIELD')
            ],
            'META_DESCRIPTION' => [
                'data_type' => 'text',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_SEOMETA_INFO_FIELD')
            ],
            'ROBOTS_INFO' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_ROBOTS_INFO_FIELD')
            ],
            'SITE_ID' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_SITE_ID')
            ],
            'LAST_DATE_CHECK' => [
                'data_type' => 'datetime',
                'title' => Loc::getMessage('SEOMETA_STATISTICS_ENTITY_LAST_DATE_CHECK_FIELD')
            ],
            'CONDITION'=>[
                'data_type' => 'Sotbit\Seometa\Orm\ConditionTable',
                'reference' => ['=this.CONDITION_ID' => 'ref.ID'],
            ]
        ];
    }

    public static function getBySessId($sess_id)
    {
        $res = self::getList(array(
            'select' => array(
                '*'
            ),
            'filter' => array(
                'SESS_ID' => $sess_id
            ),
            'order' => array(
                'ID'
            ),
            'limit' => 1
        ));
        if(isset($res) && !empty($res))
            return $res->fetch();
        else
            return false;
    }

    public static function getJson($arFilter = array())
    {
        $rsData = SeometaStatisticsTable::getList(array(
            'select' => array(
                'ID',
                'DATE_CREATE'
            ),
            'filter' => $arFilter
        ));
        $result = array();
        while ($one = $rsData->fetch()) {
            $one['DATE_CREATE'] = $one['DATE_CREATE']->toString();
            $result[] = $one;
        }
        return json_encode($result);
    }

    public static function getByDatesJson($arFilter = array())
    {
        $rsData = SeometaStatisticsTable::getList(array(
            'select' => array(
                'created_count',
                'created_date'
            ),
            'filter' => $arFilter,
            'group' => array(
                'created_date'
            ),
            'runtime' => array(
                new \Bitrix\Main\Entity\ExpressionField('created_count', 'COUNT(*)'),
                new \Bitrix\Main\Entity\ExpressionField('created_date', 'DATE(DATE_CREATE)')
            )
        ));
        $result = array();
        if(isset($rsData) && !empty($rsData))
            while ($one = $rsData->fetch()) {
                $one['created_date'] = $one['created_date']->toString();
                $result[] = $one;
            }
        return json_encode($result);
    }

    public static function getByHoursJson($arFilter = array())
    {
        $rsData = SeometaStatisticsTable::getList(array(
            'select' => array(
                'created_count',
                'created_hour'
            ),
            'filter' => $arFilter,
            'group' => array(
                'created_hour'
            ),
            'runtime' => array(
                new \Bitrix\Main\Entity\ExpressionField('created_count', 'COUNT(*)'),
                new \Bitrix\Main\Entity\ExpressionField('created_hour', 'HOUR(DATE_CREATE)')
            )
        ));
        $result = array();
        $hours = Encoding::convertEncoding(GetMessage('h'), LANG_CHARSET, "utf-8");
        if(isset($rsData) && !empty($rsData))
            while ($one = $rsData->fetch()) {
                $one['created_hour'] = $one['created_hour'] . $hours;
                $one['color'] = random_color();
                $result[] = $one;
            }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public static function getByOrdersJson($arFilter = array())
    {
        $rsData = SeometaStatisticsTable::getList(array(
            'select' => array(
                'created_count',
                'created_order'
            ),
            'filter' => $arFilter,
            'group' => array(
                'created_order'
            ),
            'runtime' => array(
                new \Bitrix\Main\Entity\ExpressionField('created_count', 'COUNT(*)'),
                new \Bitrix\Main\Entity\ExpressionField('created_order', 'ORDER_ID>0')
            )
        ));
        $result = array();
        $count = 0;
        if(isset($rsData) && !empty($rsData))
            while ($one = $rsData->fetch()) {
                $result[] = $one;
                $count += $one['created_count'];
            }
        $result1 = array();
        $mkOrder = Encoding::convertEncoding(GetMessage('SEO_META_make_order'), LANG_CHARSET, "utf-8");
        $mknOrder = Encoding::convertEncoding(GetMessage('SEO_META_not_order'), LANG_CHARSET, "utf-8");
        foreach ($result as $res) {
            $res1['percent'] = (int) round($res['created_count'] / $count * 100);
            $res1['type'] = $res['created_order'] != null ? $mkOrder : $mknOrder;
            $res1['color'] = random_color();
            $result1[] = $res1;
        }
        return json_encode($result1, JSON_UNESCAPED_UNICODE);
    }

    public static function getByFromJson($arFilter = array())
    {
        $sources = Option::get("sotbit.seometa", 'SOURCE');
        $sources = explode("\n", $sources);
        $arFrom = array();
        foreach ($sources as $s) {
            $arFrom[] = str_replace(array(
                chr(13),
                chr(9),
                ' '
            ), '', $s);
        }
        unset($arFilter['=URL_FROM']);
        
        $arFilter['!URL_FROM'] = false;
        
        $rsData = SeometaStatisticsTable::getList(array(
            'select' => array(
                'created_count',
                'URL_FROM'
            ),
            'filter' => $arFilter,
            'group' => array(
                'URL_FROM'
            ),
            'order' => array(
                'created_count' => 'desc'
            ),
            'runtime' => array(
                new \Bitrix\Main\Entity\ExpressionField('created_count', 'COUNT(*)')
            )
        ));
        
        $result1 = array();
        if(isset($rsData) && !empty($rsData))
            while ($res = $rsData->fetch()) {
                $res['color'] = random_color();
                $result1[] = $res;
            }
        return json_encode($result1);
    }

    public static function getByToJson($arFilter = array())
    {
        $rsData = SeometaStatisticsTable::getList(array(
            'select' => array(
                'created_count',
                'URL_TO'
            ),
            'filter' => $arFilter,
            'order' => array(
                'created_count' => 'desc'
            ),
            'group' => array(
                'URL_TO'
            ),
            'runtime' => array(
                new \Bitrix\Main\Entity\ExpressionField('created_count', 'COUNT(*)')
            )
        ));
        $result = array();
        $count = 0;
        if(isset($rsData) && !empty($rsData))
            while ($one = $rsData->fetch()) {
                $one['color'] = random_color();
                $one['URL_TO'] = str_replace('http://' . $_SERVER['HTTP_HOST'], '', $one['URL_TO']);
                $one['URL_TO'] = str_replace('https://' . $_SERVER['HTTP_HOST'], '', $one['URL_TO']);
                $result[] = $one;
                $count ++;
                if ($count >= 30)
                    break;
            }
        return json_encode($result);
    }

    public static function getByConditionJson($arFilter = array())
    {
        $arFilter['!CONDITION_ID'] = false;
        $rsData = SeometaStatisticsTable::getList(array(
            'select' => array(
                'created_count',
                'CONDITION_ID'
            ),
            'filter' => $arFilter,
            'order' => array(
                'created_count' => 'desc'
            ),
            'group' => array(
                'CONDITION_ID'
            ),
            'runtime' => array(
                new \Bitrix\Main\Entity\ExpressionField('created_count', 'COUNT(*)')
            )
        ));
        $result = array();
        if(isset($rsData) && !empty($rsData))
            while ($one = $rsData->fetch()) {
                $one['color'] = random_color();
                if ($one['CONDITION_ID'] != null) {
                    $one['CONDITION_NAME'] = ConditionTable::getById($one['CONDITION_ID'])->fetch();
                    $one['CONDITION_NAME'] = '#' . $one['CONDITION_ID'] . ' ' . Encoding::convertEncoding($one['CONDITION_NAME']['NAME'], LANG_CHARSET, "utf-8");
                } else {
                    $one['CONDITION_NAME'] = '-';
                }
                $result[] = $one;
            }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public static function getByPagesCountJson($arFilter = array())
    {
        $rsData = SeometaStatisticsTable::getList(array(
            'select' => array(
                'created_count',
                'PAGES_COUNT'
            ),
            'filter' => $arFilter,
            'group' => array(
                'PAGES_COUNT'
            ),
            'order' => array(
                'created_count' => 'desc'
            ),
            'runtime' => array(
                new \Bitrix\Main\Entity\ExpressionField('created_count', 'COUNT(*)')
            )
        ));
        $result = array();
        $pagesCnt = Encoding::convertEncoding(GetMessage('SEO_META_pages_cnt'), LANG_CHARSET, "utf-8");
        if(isset($rsData) && !empty($rsData))
            while ($one = $rsData->fetch()) {
                $one['color'] = random_color();
                $one['PAGES_COUNT'] = $one['PAGES_COUNT'] . $pagesCnt;

                $result[] = $one;
            }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public static function getByToTable($arFilter = array())
    {
        $rsData = SeometaStatisticsTable::getList(array(
            'select' => array(
                'created_count',
                'URL_TO'
            ),
            'filter' => $arFilter,
            'group' => array(
                'URL_TO'
            ),
            'runtime' => array(
                new \Bitrix\Main\Entity\ExpressionField('created_count', 'COUNT(*)')
            )
        ));
        $rArr = array();
        while ($one = $rsData->fetch()) {
            $rArr[] = $one;
        }
        $return = '<table class="table-url-to"><thead><th>' . GetMessage('SEO_META_TABLE_TO_URL') . '</th><th>' . GetMessage('SEO_META_TABLE_TO_COUNT') . '</th></thead>';
        foreach ($rArr as $row) {
            $return .= '<tr><td>' . $row['URL_TO'] . '</td><td>' . $row['created_count'] . '</td></tr>';
        }
        $return .= '</table>';
        
        return $return;
    }

    public static function orderInfo($arFilter = array())
    {
        unset($arFilter['=ORDER_ID']);
        $arFilter['!=ORDER_ID'] = '';
        $rsData = SeometaStatisticsTable::getList(array(
            'select' => array(
                'URL_TO',
                'ORDER_ID',
                'PAGES_COUNT',
                'DATE_CREATE'
            ),
            'filter' => $arFilter
        ));
        if (\Bitrix\Main\Loader::includeModule("sale")) {
            $rArr = array();
            $allPrices = 0;
            if(isset($rsData) && !empty($rsData))
                while ($one = $rsData->fetch()) {
                    $order = Order::getList(array(
                        'filter' => array(
                            'ID' => $one['ORDER_ID']
                        )
                    ))->fetch();
                    if (! $order)
                        continue;
                    $allPrices += $order['PRICE'];
                    $rArr[] = array(
                        $one['ORDER_ID'],
                        $one['URL_TO'],
                        round($order['PRICE'], 2),
                        $one['DATE_CREATE']->toString(),
                        $one['PAGES_COUNT']

                    );
                }
        }
        return json_encode($rArr);
    }
}

function random_color_part()
{
    return str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
}

function random_color()
{
    return '#' . random_color_part() . random_color_part() . random_color_part();
}