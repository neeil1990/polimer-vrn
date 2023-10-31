<?php

namespace Yandex\Market\Trading\State\Internals;

use Yandex\Market;
use Bitrix\Main;

class DataTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_trading_state_data';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\StringField('SERVICE', [
				'required' => true,
				'primary' => true,
				'validation' => [__CLASS__, 'getValidationForService'],
			]),
			new Main\Entity\StringField('ENTITY_ID', [
				'required' => true,
				'primary' => true,
				'validation' => [__CLASS__, 'getValidationForEntityId'],
			]),
			new Main\Entity\StringField('NAME', [
				'required' => true,
				'primary' => true,
				'validation' => [__CLASS__, 'getValidationForName'],
			]),
			new Main\Entity\TextField('VALUE', Market\Reference\Storage\Field\FuzzySerializer::getParameters() + [
				'required' => true,
			]),
			new Main\Entity\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
		];
	}

	public static function getValidationForService()
	{
		return [
			new Main\Entity\Validator\Length(null, 20),
		];
	}

	public static function getValidationForEntityId()
	{
		return [
			new Main\Entity\Validator\Length(null, 12),
		];
	}

	public static function getValidationForName()
	{
		return [
			new Main\Entity\Validator\Length(null, 60),
		];
	}
}