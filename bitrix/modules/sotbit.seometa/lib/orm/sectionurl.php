<?php
namespace Sotbit\Seometa\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class SeometaSectionChpuTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATE_CHANGE datetime mandatory
 * <li> DATE_CREATE datetime optional
 * <li> ACTIVE bool optional default 'Y'
 * <li> SORT int optional default 500
 * <li> NAME string(255) optional
 * <li> DESCRIPTION string optional
 * <li> PARENT_CATEGORY_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Sotbit
 **/

class SectionUrlTable extends \DataManagerEx_SeoMeta
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_sotbit_seometa_section_chpu';
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
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_ID_FIELD'),
            ),
            'DATE_CHANGE' => array(
                'data_type' => 'datetime',
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_DATE_CHANGE_FIELD'),
            ),
            'DATE_CREATE' => array(
                'data_type' => 'datetime',
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_DATE_CREATE_FIELD'),
            ),
            'ACTIVE' => array(
                'data_type' => 'boolean',
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_ACTIVE_FIELD'),
            ),
            'SORT' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_SORT_FIELD'),
            ),
            'NAME' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateName'),
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_NAME_FIELD'),
            ),
            'DESCRIPTION' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_DESCRIPTION_FIELD'),
            ),
            'PARENT_CATEGORY_ID' => array(
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_SECTION_CHPU_ENTITY_PARENT_CATEGORY_ID_FIELD'),
            ),    
        );
    }

    public static function deleteSections($id, $del = false)
    {
        $sect = self::query()->addSelect('*')->setFilter(['ID' => $id])->fetch();
        $seometaUrlCollection = SeometaUrlTable::query()
            ->setFilter(['CATEGORY_ID' => $id])
            ->addSelect('*')
            ->fetchCollection();

        $elementsCollection = $seometaUrlCollection->getAll();
        foreach ($elementsCollection as $element) {
            $element->set('CATEGORY_ID', $sect['PARENT_CATEGORY_ID']);
        }

        $seometaUrlCollection->save();

        $arSections = self::query()->addSelect('*')->setFilter(['PARENT_CATEGORY_ID' => $id])->fetchAll();
        foreach ($arSections as $section) {
            $res = parent::update($section['ID'], ['PARENT_CATEGORY_ID' => $sect['PARENT_CATEGORY_ID']]);
        }

        if (!$res || $res->isSuccess()) {
            $result = parent::delete($id);
        }

        return $result;
    }

    /**
     * Returns validators for NAME field.
     *
     * @return array
     */
    public static function validateName()
    {
        return array(
            new Main\Entity\Validator\Length(null, 255),
        );
    }
}