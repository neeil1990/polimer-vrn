<?
namespace Sotbit\Seometa;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class AutogenerationTable extends \DataManagerEx_SeoMeta
{
	public static function getTableName()
	{
		return 'b_sotbit_seometa_autogeneration';
	}

	public static function getMap()
	{
		return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_ID_FIELD')
            )),
			new Entity\StringField('NAME', array(
                'required' => true,
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_NAME_FIELD')
            )),
            new Entity\StringField('SITES', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_SITES_FIELD')
            )),
            new Entity\StringField('TYPE_OF_INFOBLOCK', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_TYPE_OF_INFOBLOCK_FIELD')
            )),
            new Entity\StringField('INFOBLOCK', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_INFOBLOCK_FIELD')
            )),
            new Entity\StringField('SECTIONS', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_SECTIONS_FIELD')
            )),
            new Entity\TextField('RULE', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_RULE_FIELD')
            )),
            new Entity\StringField('LOGIC', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_LOGIC_FIELD')
            )),
            new Entity\StringField('FILTER_TYPE', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_FILTER_TYPE_FIELD')
            )),
            new Entity\StringField('NAME_TEMPLATE', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_NAME_TEMPLATE_FIELD')
            )),
            new Entity\BooleanField('ACTIVE', array(
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_ACTIVE_FIELD')
            )),
            new Entity\BooleanField('SEARCH', array(
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_SEARCH_FIELD')
            )),
            new Entity\BooleanField('NO_INDEX', array(
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_NO_INDEX_FIELD')
            )),
            new Entity\BooleanField('STRICT', array(
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_STRICT_FIELD')
            )),
            new Entity\IntegerField('CATEGORY', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_CATEGORY_FIELD')
            )),
            new Entity\TextField('META', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_META_FIELD')
            )),
            new Entity\TextField('TAGS', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_TAGS_FIELD')
            )),
            new Entity\StringField('NEW_URL_TEMPLATE', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_NEW_URL_TEMPLATE_FIELD')
            )),
            new Entity\BooleanField('GENERATE_CHPU', array(
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_GENERATE_CHPU_FIELD')
            )),
            new Entity\DatetimeField('DATE_CHANGE', array(
                'title' => Loc::getMessage('SEOMETA_AUTOGENERATION_ENTITY_DATE_CHANGE_FIELD')
            )),
		);
	}
}
