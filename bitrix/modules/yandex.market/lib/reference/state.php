<?php

namespace Yandex\Market\Reference;

use Bitrix\Main;

class StateTable extends Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_state';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\StringField('NAME', [
				'required' => true,
				'primary' => true,
				'size' => 50,
				'validation' => [__CLASS__, 'validateName']
			]),
			new Main\Entity\TextField('VALUE', [
				'required' => true,
			]),
		];
	}

	public static function validateName()
	{
		return [
			new Main\Entity\Validator\Length(null, 50),
		];
	}
}