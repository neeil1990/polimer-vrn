<?php

namespace Yandex\Market\Ui\Checker\Internals;

use Bitrix\Main;
use Yandex\Market;

class HistoryTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_checker_history';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),
			new Main\Entity\StringField('TEST', [
				'required' => true,
			]),
			new Main\Entity\StringField('MESSAGE', [
				'required' => true,
			]),
			new Main\Entity\StringField('CODE', [
				'required' => true,
			]),
			new Main\Entity\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
			new Main\Entity\BooleanField('RESOLVED', [
				'values' => [ static::BOOLEAN_N, static::BOOLEAN_Y ],
				'default_value' => static::BOOLEAN_N,
			]),
		];
	}
}