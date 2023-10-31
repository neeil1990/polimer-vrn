<?php
namespace Sotbit\Seometa\Orm;

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
        return [
            'ID' => [
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('SEOMETA_CHPU_SEO_DATA_ID'),
            ],
            'BITRIX_URL' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_CHPU_SEO_DATA_BITRIX_URL'),
            ],
            'CONDITION_ID' => [
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_CHPU_SEO_DATA_CONDITION_ID'),
            ],
            'SEOMETA_DATA' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_CHPU_SEO_DATA_SEO_DATA'),
            ],
            'SITE_ID' => [
                'data_type' => 'string',
                'title' => Loc::getMessage('SEOMETA_SITE_ID')
            ],
            'CHPU'=>[
                'data_type' => 'Sotbit\Seometa\Orm\SeometaUrlTable',
                'reference' => ['=this.BITRIX_URL' => 'ref.REAL_URL','=this.SITE_ID' => 'ref.SITE_ID'],
            ],
        ];
    }

    /**
     * Delete seo data by ID
     *
     * @param $id
     */
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

    /**
     * Delete seo data by bitrix url and site ID
     *
     * @param $url
     */
    public static function deleteByBitrixUrl($url, $site_id)
    {
        $arr = self::getList([
            'select' => [
                'ID',
                'SEOMETA_DATA'
            ],
            'filter' => [
                'SITE_ID' => $site_id,
                'BITRIX_URL' => $url
            ],
            'order' => ['ID'],
        ]);

        while ($one = $arr->fetch()) {
            if ($data = unserialize($one['SEOMETA_DATA'])) {
                \CFile::Delete($data['ELEMENT_FILE']);
            }

            self::delete($one['ID']);
        }
    }

    /**
     * Delete seo data by condition ID
     *
     * @param $conditionID
     */
    public static function deleteByConditionId($conditionID){
        $arr = self::getList([
            'select' => ['ID', 'SEOMETA_DATA'],
            'filter' => ['CONDITION_ID' => $conditionID],
            'order'  => ['ID'],
        ]);

        while($one = $arr->fetch()){
            if($data = unserialize($one['SEOMETA_DATA'])) {
                \CFile::Delete($data['ELEMENT_FILE']);
            }

            self::delete($one['ID']);
        }
    }

    /**
     * Get seo data by condition ID
     *
     * @param $conditionId
     * @return array
     */
    public static function getByConditionId($conditionId)
    {
        $res = self::getList([
            'select' => ['*'],
            'filter' => [
                'CONDITION_ID' => $conditionId
            ],
            'order'  => ['ID'],
        ]);
        $resAll = [];

        while($one = $res->fetch())
        {
            if($data = unserialize($one['SEOMETA_DATA'])) {
                $one = array_merge($one, $data);
            }
            $resAll[$one['ID']] = $one;
        }
        return $resAll;
    }

    /**
     * Get seo data by bitrix URL and SITE_ID
     *
     * @param $bitrixUrl
     * @param $id
     * @return array|false
     */
    public static function getByBitrixUrl(
        $bitrixUrl,
        $site_id
    ) {
        $res = self::getList([
            'select' => ['*'],
            'filter' => [
                'BITRIX_URL' => $bitrixUrl,
                'SITE_ID' => $site_id,
            ],
            'order'  => ['ID'],
        ]);
        $one = $res->fetch();

        if($data = unserialize($one['SEOMETA_DATA'])) {
            $one = array_merge($one, $data);
        }

        return $one;
    }

    /**
     * Get all records seo data
     *
     * @return array
     */
    public static function getAll(){
        $res = self::getList([
            'select' => ['*'],
            'order'  => ['ID'],
        ]);
        $resAll = [];

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