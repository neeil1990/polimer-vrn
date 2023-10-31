<?php
namespace Twinpx\Yadelivery;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

class TwinpxStateTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_twpx_yadelivery_state';
	}
	
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('YADELIVERY_STATE_ENTITY_ID_FIELD')
				]
			),
			new StringField(
				'CODE',
				[
					'validation' => [__CLASS__, 'validateCode'],
					'title' => Loc::getMessage('YADELIVERY_STATE_ENTITY_CODE_FIELD')
				]
			),
			new StringField(
				'VALUE',
				[
					'validation' => [__CLASS__, 'validateValue'],
					'title' => Loc::getMessage('YADELIVERY_STATE_ENTITY_VALUE_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return [
			new LengthValidator(null, 50),
		];
	}

	/**
	 * Returns validators for VALUE field.
	 *
	 * @return array
	 */
	public static function validateValue()
	{
		return [
			new LengthValidator(null, 255),
		];
	}
	
	
	public static function getByCode($code){
		if(!$code)
			return;
			
		$value = TwinpxStateTable::getRow(array('filter' => array('=CODE' => $code)));
		
		return $value['VALUE'];
	}
}