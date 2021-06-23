<?php

namespace Yandex\Market\Trading\Procedure;

use Yandex\Market;
use Bitrix\Main;

class QueueTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_trading_queue';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Main\Entity\IntegerField('SETUP_ID', [
				'required' => true,
			]),
			new Main\Entity\StringField('PATH', [
				'required' => true,
				'validation' => [__CLASS__, 'validatePath'],
			]),
			new Main\Entity\TextField('DATA', [
				'required' => true,
				'serialized' => true,
			]),
			new Main\Entity\EnumField('ENTITY_TYPE', [
				'required' => true,
				'values' => [
					Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER,
				],
			]),
			new Main\Entity\StringField('ENTITY_ID', [
				'required' => true,
				'validation' => [__CLASS__, 'validateEntityId'],
			]),
			new Main\Entity\DatetimeField('EXEC_DATE', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('EXEC_COUNT', [
				'default_value' => 0,
			]),
			new Main\Entity\IntegerField('INTERVAL', [
				'required' => true,
				'default_value' => 3600,
			])
		];
	}

	public static function validatePath()
	{
		return [
			new Main\Entity\Validator\Length(null, 50),
		];
	}

	public static function validateEntityId()
	{
		return [
			new Main\Entity\Validator\Length(null, 50),
		];
	}
}