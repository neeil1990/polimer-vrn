<?php

namespace Corsik\YaDelivery\Table;

use Bitrix\Main\Entity;

class WarehousesTable extends Entity\DataManager
{

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'corsik_yadelivery_warehouses';
	}

	public static function getMapMatchArray($arr)
	{
		$result = [];
		foreach (self::getMap() as $field)
		{
			$code = $field->getName();
			$result[$code] = $arr[$code];
		}

		return $result;
	}

	public static function getMap()
	{
		return [
			new Entity\StringField('SITE_ID', [
				'required' => true,
			]),
			new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Entity\StringField('ACTIVE', [
				'required' => true,
				'default_value' => 'Y',
			]),
			new Entity\IntegerField('SORT', [
				'required' => true,
				'default_value' => 500,
			]),
			new Entity\StringField('NAME', [
				'required' => true,
				'validator' => function () {
					return new Entity\Validator\Length(null, 255);
				},
			]),
			new Entity\IntegerField('ZONE_ID', [
				'required' => true,
				'validation' => function () {
					return [
						new Entity\Validator\Foreign(ZonesTable::getEntity()->getField('ID')),
					];
				},
			]),
			new Entity\TextField('COORDINATES'),
		];
	}
}
