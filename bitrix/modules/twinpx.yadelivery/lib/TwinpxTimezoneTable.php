<?php
namespace Twinpx\Yadelivery;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

class TwinpxTimezoneTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_twpx_yadelivery_timezone';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('YADELIVERY_TIMEZONE_ENTITY_ID_FIELD')
				]
			),
			new StringField(
				'BX_CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateBxCode'],
					'title' => Loc::getMessage('YADELIVERY_TIMEZONE_ENTITY_BX_CODE_FIELD')
				]
			),
			new StringField(
				'UTC',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateUtc'],
					'title' => Loc::getMessage('YADELIVERY_TIMEZONE_ENTITY_UTC_FIELD')
				]
			),
			new StringField(
				'REGION',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateRegion'],
					'title' => Loc::getMessage('YADELIVERY_TIMEZONE_ENTITY_REGION_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for BX_CODE field.
	 *
	 * @return array
	 */
	public static function validateBxCode()
	{
		return [
			new LengthValidator(null, 20),
		];
	}

	/**
	 * Returns validators for UTC field.
	 *
	 * @return array
	 */
	public static function validateUtc()
	{
		return [
			new LengthValidator(null, 10),
		];
	}

	/**
	 * Returns validators for REGION field.
	 *
	 * @return array
	 */
	public static function validateRegion()
	{
		return [
			new LengthValidator(null, 100),
		];
	}
}